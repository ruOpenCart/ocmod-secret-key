<?php

namespace Opencart\Admin\Controller\Extension\OcnSecretKey\Module;

class OcnSecretKey extends \Opencart\System\Engine\Controller
{
	public function index(): void
	{
		$this->load->language('extension/ocn_secret_key/module/ocn_secret_key');
		
		$this->document->setTitle($this->language->get('heading_title'));
		
		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
			],
			[
				'text' => $this->language->get('text_extension'),
				'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
			],
			[
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/ocn_secret_key/module/ocn_secret_key', 'user_token=' . $this->session->data['user_token'])
			]
		];
		
		$data['save'] = $this->url->link('extension/ocn_secret_key/module/ocn_secret_key.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');
		
		$data['module_ocn_secret_key_status'] = $this->config->get('module_ocn_secret_key_status');
		$data['module_ocn_secret_key_secret_key'] = $this->config->get('module_ocn_secret_key_secret_key');
		$data['module_ocn_secret_key_secret_value'] = $this->config->get('module_ocn_secret_key_secret_value');
		$data['module_ocn_secret_key_message'] = $this->config->get('module_ocn_secret_key_message');
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('extension/ocn_secret_key/module/ocn_secret_key', $data));
	}
	
	public function save(): void
	{
		$this->load->language('extension/ocn_secret_key/module/ocn_secret_key');
		
		$json = [];
		
		if (!$this->user->hasPermission('modify', 'extension/ocn_secret_key/module/ocn_secret_key')) {
			$json['error'] = $this->language->get('error_permission');
		}
		
		if (!$json) {
			$this->load->model('setting/setting');
			
			$this->model_setting_setting->editSetting('module_ocn_secret_key', $this->request->post);
			
			$this->load->model('setting/event');
			
			$status = $this->request->post['module_ocn_secret_key_status']
				? 1
				: 0;
			$event = $this->model_setting_event->getEventByCode('ocn_secret_key_prepare');
			$this->model_setting_event->editStatus($event['event_id'], $status);
			$event = $this->model_setting_event->getEventByCode('ocn_secret_key_check');
			$this->model_setting_event->editStatus($event['event_id'], $status);
			
			$json['success'] = $this->language->get('text_success');
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function prepare(): void
	{
		if ($this->config->get('module_ocn_secret_key_status')) {
			$this->load->language('extension/ocn_secret_key/module/ocn_secret_key');
			
			if (isset($this->request->get[$this->config->get('module_ocn_secret_key_secret_key')])) {
				$this->session->data[$this->config->get('module_ocn_secret_key_secret_key')] = $this->request->get[$this->config->get('module_ocn_secret_key_secret_key')];
			} else {
				unset($this->session->data[$this->config->get('module_ocn_secret_key_secret_key')]);
			}
		}
	}
	
	public function check(): void
	{
		if (
			$this->config->get('module_ocn_secret_key_status')
			&& $this->config->get('module_ocn_secret_key_secret_key')
			&& $this->config->get('module_ocn_secret_key_secret_value')
		) {
			$json = [];
			$this->load->language('extension/ocn_secret_key/module/ocn_secret_key');
			
			if (
				!isset($this->session->data[$this->config->get('module_ocn_secret_key_secret_key')])
				|| $this->session->data[$this->config->get('module_ocn_secret_key_secret_key')] !== $this->config->get('module_ocn_secret_key_secret_value')
			) {
				$json['error'] = $this->config->get('module_ocn_secret_key_message') != ''
					? $this->config->get('module_ocn_secret_key_message')
					: $this->language->get('error_login');
			}
			
			if (count($json)) {
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode($json));
			}
		}
	}
	
	public function install(): void
	{
		if ($this->user->hasPermission('modify', 'extension/module')) {
			$this->load->model('setting/setting');
			$data = [
				'module_ocn_secret_key_status' => 0,
				'module_ocn_secret_key_secret_key' => 'secret',
				'module_ocn_secret_key_secret_value' => 'secret',
				'module_ocn_secret_key_message' => '',
			];
			$this->model_setting_setting->editSetting('module_ocn_secret_key', $data);
			
			$this->load->model('setting/event');
			
			$this->model_setting_event->addEvent([
				'code' => 'ocn_secret_key_prepare',
				'description' => 'Подготовка и проверка наличия полей',
				'trigger' => 'admin/controller/common/login/before',
				'action' => 'extension/ocn_secret_key/module/ocn_secret_key.prepare',
				'status' => 0,
				'sort_order' => 1,
			]);
			$this->model_setting_event->addEvent([
				'code' => 'ocn_secret_key_check',
				'description' => 'Проверка ключа и значения',
				'trigger' => 'admin/controller/common/login.login/after',
				'action' => 'extension/ocn_secret_key/module/ocn_secret_key.check',
				'status' => 0,
				'sort_order' => 1,
			]);
		}
	}
	
	public function uninstall(): void
	{
		if ($this->user->hasPermission('modify', 'extension/module')) {
			$this->load->model('setting/event');
			$this->model_setting_event->deleteEventByCode('ocn_secret_key_prepare');
			$this->model_setting_event->deleteEventByCode('ocn_secret_key_check');
		}
	}
}
