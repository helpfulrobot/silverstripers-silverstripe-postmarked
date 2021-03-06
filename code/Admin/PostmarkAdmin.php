<?php
/**
 * Created by Nivanka Fonseka (nivanka@silverstripers.com).
 * User: nivankafonseka
 * Date: 9/6/15
 * Time: 9:25 AM
 * To change this template use File | Settings | File Templates.
 */

use Postmark\PostmarkClient;

class PostmarkAdmin extends ModelAdmin {

	private static $url_segment = 'messages';
	private static $menu_title = 'Conversations';
	private static $menu_icon = 'silverstripe-postmarked/images/icons/post.png';

	private static $managed_models = array(
		'PostmarkMessage'
	);

	private static $allowed_actions = array(
		'MessageForm',
		'MessagePopupContents'
	);

	public function init() {
		parent::init();
		$this->showImportForm = false;
		Requirements::css(POSTMARK_RELATIVE_PATH . '/css/icons.css');
	}


	public function getEditForm($id = null, $fields = null){
		$form = parent::getEditForm($id = null, $fields = null);

		if($this->modelClass == 'PostmarkMessage'){
			$fields = $form->Fields();
			$grid = $fields->dataFieldByName($this->sanitiseClassName($this->modelClass));
			if($grid){
				$configs = $grid->getConfig();
				$configs->removeComponentsByType('GridFieldAddNewButton');
				$configs->removeComponentsByType('GridFieldExportButton');
				$configs->removeComponentsByType('GridFieldPrintButton');

				$editForm = $configs->getComponentByType('GridFieldDetailForm');
				$editForm->setItemRequestClass('PostmarkMessageGridFieldDetailForm_ItemRequest');

				$configs->addComponent(new GridFieldMessageStatusColumn(), 'GridFieldDataColumns');

			}

		}

		return $form;
	}

	public function getList(){
		$list = parent::getList();
		if($this->modelClass == 'PostmarkMessage'){
			$list = $list->filter('InReplyToID', 0)->sort('LastEdited DESC');
		}
		return $list;
	}


	public function getSearchContext(){
		if($this->modelClass == 'PostmarkMessage'){
			$context = new MessageSearchContext('PostmarkMessage');
			foreach($context->getFields() as $field){

				if(isset($_REQUEST['q']) && isset($_REQUEST['q'][$field->getName()])){
					$field->setValue($_REQUEST['q'][$field->getName()]);
				}
				$field->setName(sprintf('q[%s]', $field->getName()));
			}
			foreach($context->getFilters() as $filter){
				$filter->setFullName(sprintf('q[%s]', $filter->getFullName()));
			}
			return $context;
		}
		return parent::getSearchContext();
	}



	public function MessageForm($request = null, $itemID = 0){
		if($itemID == 0){
			$itemID = isset($_REQUEST['ToMemberID']) ? $_REQUEST['ToMemberID'] : 0;
		}

		$mergeText = "<ul><li>{" . implode("}</li><li>{", PostmarkHelper::MergeTags()) . "}</li></ul>";

		$form = new Form(
			$this,
			'MessageForm',
			new FieldList(array(
				ObjectSelectorField::create('ToMemberID', 'To:')->setValue($itemID)->setSourceObject(Config::inst()->get('PostmarkAdmin', 'member_class'))->setDisplayField('Email'),
				DropdownField::create('FromID', 'From')->setSource(PostmarkSignature::get()->filter('IsActive', 1)->map('ID', 'Email')->toArray()),
				TextField::create('Subject'),
				QuillEditorField::create('Body'),
				LiteralField::create('MergeTypes', '<div class="varialbes toggle-block">
					<h4>Merge Values</h4>
					<div class="contents">' . $mergeText . '</div>
				</div>'),
				HiddenField::create('InReplyToID')->setValue(isset($_REQUEST['ReplyToMessageID']) ? $_REQUEST['ReplyToMessageID'] : 0),
				FileField::create('Attachment_1', 'Attachment One'),
				FileField::create('Attachment_2', 'Attachment Two'),
				FileField::create('Attachment_3', 'Attachment Three'),
				FileField::create('Attachment_4', 'Attachment Four'),
				FileField::create('Attachment_5', 'Attachment Five')
			)),
			new FieldList(FormAction::create('postmessage', 'Sent Message')
		));

		$requiredField = new RequiredFields(array(
			'FromID',
			'Subject',
			'Body'
		));
		$form->setValidator($requiredField);

		$this->extend('updateMessageForm', $form, $itemID);

		$form->setFormAction($this->Link('PostmarkMessage/MessageForm'));
		return $form;

	}

	public function MessagePopupContents(){
		$form = $this->MessageForm();

		$form->Fields()->dataFieldByName('Subject')->setValue($_GET['Subject']);
		$form->Fields()->dataFieldByName('FromID')->setValue($_GET['FromID']);
		$form->Fields()->dataFieldByName('ToMemberID')->setValue(array(
			$_GET['ToID']
		));

		return $form->forTemplate();
	}

	public function postmessage($data, $form){

		$signature = PostmarkSignature::get()->byID($data['FromID']);
		PostmarkMailer::RecordEmails(true);
		PostmarkMailer::ReplyToMessageID($data['InReplyToID']);

		$clients = PostmarkHelper::client_list()->filter('ID', $data['ToMemberID']);
		foreach($clients as $client){
			$email = new Email(
				$signature->Email,
				$client->Email,
				$data['Subject'],
				PostmarkHelper::MergeEmailText($data['Body'], $client)
			);

			for($i = 1; $i <= 5; $i+=1){
				$strKey = 'Attachment_' . $i;
				if(isset($_FILES[$strKey]) && $_FILES[$strKey]['tmp_name']){
					$contents = file_get_contents($_FILES[$strKey]['tmp_name']);
					if(strlen($contents)){
						$email->attachFileFromString($contents, $_FILES[$strKey]['name']);
					}
				}
			}


			$this->extend('updatePostmessage', $email, $data);


			$email->send();
		}

		PostmarkMailer::RecordEmails(false);
		PostmarkMailer::ReplyToMessageID(0);


	}

} 