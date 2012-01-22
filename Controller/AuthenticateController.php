<?php

class AuthenticateController extends OfbidAppController
{
	public $uses = array();
	
	public function index()
	{
		$this->autoRender = false;
		$this->response->type('text');
		$userModel = Configure::read("Ofbid.userModelName");
		$listener = Configure::read('Ofbid.loginListener');
		if ($listener != null)
		{
			$l = new $listener();
			$this->getEventManager()->attach($l);
		}
		
		if ($this->request->is('post'))
		{
			$this->request->data['audience'] = $_SERVER['SERVER_NAME'];
			$req = json_decode($this->__simplePost('https://browserid.org/verify', $this->request->data));
			
			if ($req->status == 'okay')
			{
				$this->loadModel($userModel);
				$user = $this->$userModel->findByEmail($req->email);
				if ($user)
				{
					$this->Auth->login($user[$userModel]);
					$this->Session->setFlash(Configure::read("Ofbid.successfulLoginMessage"));
					$this->response->body($this->Auth->redirect());
					$this->getEventManager()->dispatch(new CakeEvent('Ofbid.successfullLogin', $this, array('user'=>$user)));
				}
				else
				{
					$this->Session->setFlash(Configure::read("Ofbid.emailNotFoundMessage"));
					$this->response->body(Configure::read("Ofbid.emailNotFoundRedirect"));
				}
			}
			else
			{
				$this->Session->setFlash(Configure::read("Ofbid.defaultErrorMessage"));
				$this->response->body(Configure::read("Ofbid.defaultErrorRedirect"));
			}
		}
		else
			$this->response->body('nopost');
	}
	
	function beforeFilter()
	{
		$this->Auth->allow('index');
		parent::beforeFilter();
	}
	
	function __simplePost($url, $data) 
	{ 
		$fields_string = http_build_query($data);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
}