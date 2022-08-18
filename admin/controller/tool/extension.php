<?php
namespace Opencart\Admin\Controller\Tool;

class Extension extends \Opencart\System\Engine\Controller
{
    public function index(): void {
        $this->load->language('tool/extension');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('tool/extension', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['user_token'] = $this->session->data['user_token'];

        $data['detail'] = $this->url->link('tool/extension|detail', 'user_token=' . $this->session->data['user_token']);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('tool/extension', $data));
    }

    public function detail():void {
        $this->load->language('tool/extension');
        $this->document->setTitle($this->language->get('heading_title'));

        if (isset($this->request->get['extension_install_id'])) {
            $data['extension_install_id'] = (int) $this->request->get['extension_install_id'];
        } else {
            $data['extension_install_id'] = -1;
        }

        if (isset($this->request->get['code'])) {
            $data['code'] = $this->request->get['code'];
        } else {
            $data['code'] = '';
        }

        if (isset($this->request->get['name'])) {
            $heading_title = $this->request->get['name'];
        } else {
            $heading_title= $data['code'];
        }

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('tool/extension', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['breadcrumbs'][] = [
            'text' => $heading_title,
            'href' => 'javascript:void(0)'
        ];

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('tool/extension_detail', $data));
    }

    public function getListExtensionGroup():void {
        $this->load->model('tool/extension');
        $data = $this->model_tool_extension->getListExtensionGroup();
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function getExtensionGroupDetail():void {
        if (isset($this->request->post['extension_install_id'])) {
            $extension_install_id= (int) $this->request->post['extension_install_id'];
        } else {
            $extension_install_id = -1;
        }

        if (isset($this->request->post['code'])) {
            $code = $this->request->post['code'];
        } else {
            $code  = '';
        }

        $this->load->model('tool/extension');
        $data = $this->model_tool_extension->getExtensionGroupDetail($code, $extension_install_id);
        $data['data'] = $this->createLink($data['data'], $code);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function addExtensionGroup():void {
        $this->load->language('tool/extension');
        $json = array();
        if (isset($this->request->post['extension_install_id'])) {
            $json['extension_install_id'] = (int) $this->request->post['extension_install_id'];
        } else {
            $json['extension_install_id'] = -1;
        }

        if (isset($this->request->post['extension_id'])) {
            $json['extension_id'] = (int) $this->request->post['extension_id'];
        } else {
            $json['extension_id'] = 0;
        }

        if (isset($this->request->post['extension_download_id'])) {
            $json['extension_download_id'] = (int) $this->request->post['extension_download_id'];
        } else {
            $json['extension_download_id'] = 0;
        }

        if (isset($this->request->post['name'])) {
            $json['name'] = (string) $this->request->post['name'];
        } else {
            $json['name'] = "";
        }

        if (isset($this->request->post['package_name'])) {
            $json['package_name'] = (string) $this->request->post['package_name'];
        } else {
            $json['package_name'] = "";
        }

        if (isset($this->request->post['code'])) {
            $json['code'] = (string) $this->request->post['code'];
        } else {
            $json['code'] = "";
        }

        if (isset($this->request->post['version'])) {
            $json['version'] = (string) $this->request->post['version'];
        } else {
            $json['version'] = "";
        }

        if (isset($this->request->post['author'])) {
            $json['author'] = (string) $this->request->post['author'];
        } else {
            $json['author'] = "";
        }

        if (isset($this->request->post['link'])) {
            $json['link'] = (string) $this->request->post['link'];
        } else {
            $json['link'] = "";
        }

        if (isset($this->request->post['status'])) {
            $json['status'] = (int) $this->request->post['status'];
        } else {
            $json['status'] = 0;
        }

        $this->load->model('tool/extension');
        $data = $this->model_tool_extension->addExtensionGroup($json);

        if(array_key_exists('error', $data)){
            $data['error']['msg'] = $this->language->get($data['error']['msg']);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function addExtension():void {
        $json = array();
        $data = array();
        $this->load->language('tool/extension');

        if (isset($this->request->post['extension_install_id'])) {
            $json['extension_install_id'] = (int) $this->request->post['extension_install_id'];
        } else {
            $json['extension_install_id'] = -1;
        }

        if (isset($this->request->post['code'])) {
            $json['code'] = (string) $this->request->post['code'];

            if (isset($this->request->post['type'])) {
                $json['type'] = (string) $this->request->post['type'];
            } else {
                $json['type'] = "";
            }

            if (isset($this->request->post['extension'])) {
                $json['extension'] = (string) $this->request->post['extension'];
            } else {
                $json['extension'] = "";
            }

            if (isset($this->request->post['name'])) {
                $json['name'] = (string) $this->request->post['name'];
            } else {
                $json['name'] = "Name of {$json['code']}";
            }

            if (isset($this->request->post['paths'])) {
                $json['paths'] = (string) $this->request->post['paths'];
            } else {
                $json['paths'] = "";
            }

            $json['paths'] = explode(',', $json['paths']);

        } else {
            $data['error'] = array(
                'code' => 0,
                'msg' => $this->language->get('invalid_ext_code')
            );
        }

        $this->load->model('tool/extension');
        $data = $this->model_tool_extension->createExtension($json);

        if(array_key_exists('error', $data)){
            $data['error']['msg'] = $this->language->get($data['error']['msg']);
        }
        else {
            $this->load->model('user/user_group');
            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/opencart/'.$json['type'].'/' . $json['code']);
            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/opencart/'.$json['type'].'/' . $json['code']);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    public function deleteExtension():void {
        $data = array();
        if (isset($this->request->post['code'])) {
            $json['code'] = (string) $this->request->post['code'];
        } else {
            $json['code'] = '';
        }

        if (isset($this->request->post['type'])) {
            $json['type'] = (string) $this->request->post['type'];
        } else {
            $json['type'] = '';
        }

        if (isset($this->request->post['extension'])) {
            $json['extension'] = (string) $this->request->post['extension'];
        } else {
            $json['extension'] = '';
        }
        if($json['code'] != '' && $json['type'] != '' && $json['extension'] != ''){
            $this->load->model('setting/extension');
            $this->model_setting_extension->uninstall($json['type'], $json['code']);

            // Call uninstall method if it exists
            $this->load->controller('extension/' . $json['extension'] . '/'.$json['type'].'/' . $json['code'] . '|uninstall');

            // Delete from database and file
            $this->load->model('tool/extension');
            $data = $this->model_tool_extension->deleteExtension($json['code'], $json['type']);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    private function createLink(array $data, string $extension):array {
        $output = array();
        foreach ($data as $item){
            $param = "user_token={$this->session->data['user_token']}&extension=$extension&code={$item['code']}";
            $item['install'] = [
                'text' => $this->language->get('button_install'),
                'href' => $this->url->link("extension/{$item["type"]}|install", $param)
            ];
            $item['uninstall'] = [
                'text' => $this->language->get('button_uninstall'),
                'href' => $this->url->link("extension/{$item["type"]}|uninstall", $param)
            ];
            $item['delete'] = [
                'text' => $this->language->get('button_delete'),
                'href' => $this->url->link("tool/extension|deleteExtension", $param)
            ];
            $item['view'] = [
                'text' => $this->language->get('button_view'),
                'href' => $this->url->link("extension/opencart/{$item['type']}/{$item['code']}", $param)
            ];

            $output[] = $item;
        }
        return $output;
    }
}