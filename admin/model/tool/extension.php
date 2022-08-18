<?php


namespace Opencart\Admin\Model\Tool;

use mysql_xdevapi\Exception;

class Extension extends \Opencart\System\Engine\Model
{
    public function isDefault($code, $id = 0){
        return $code == 'opencart' || $id == 1;
    }

    public function allExtensionType(){
        return ['analytics', 'captcha', 'currency', 'dashboard', 'feed', 'fraud', 'module', 'other', 'payment', 'report', 'shipping', 'theme', 'total'];
    }

    public function getListExtensionGroup():array
    {
        $db_prefix = DB_PREFIX;
        $query = $this->db->query("SELECT * FROM `{$db_prefix}extension_install`");
        return $query->rows;
    }

    public function addExtensionGroup(array $json):array {
        $db_prefix = DB_PREFIX;
        if($json['extension_install_id'] < 0){
            $sql = "INSERT INTO `{$db_prefix}extension_install` (`extension_install_id`, `extension_id`, `extension_download_id`, `name`, `package_name`, `code`, `version`, `author`, `link`, `status`, `date_added`) VALUES (NULL, '{$json['extension_id']}', '{$json['extension_download_id']}','{$this->db->escape($json['name'])}', '{$this->db->escape($json['package_name'])}', '{$this->db->escape($json['code'])}', '{$this->db->escape($json['version'])}', '{$this->db->escape($json['author'])}', '{$this->db->escape($json['link'])}', '{$json['status']}', NOW()) ";
        }
        else {
            $sql = "UPDATE `{$db_prefix}extension_install` SET `extension_id`='{$json['extension_id']}', `extension_download_id`='{$json['extension_download_id']}', `name`='{$this->db->escape($json['name'])}', `package_name`='{$this->db->escape($json['package_name'])}', `code`='{$this->db->escape($json['code'])}', `version`='{$this->db->escape($json['version'])}', `author`='{$this->db->escape($json['author'])}', `link`='{$this->db->escape($json['link'])}', `status`='{$json['status']}'    WHERE `extension_install_id`='{$json['extension_install_id']}'";
        }

        $sql1 = "SELECT * FROM `{$db_prefix}extension_install` WHERE `code`='{$this->db->escape($json['code'])}'";

        $data = array();
        try {
            $num = count($this->db->query($sql1)->rows);
            //$num = 1;
            if($num > 0 && $json['extension_install_id'] < 0){
                $data['error'] = array(
                    'code' => 1,
                    'msg' => 'invalid_ext_code'
                );
            }
            else {
                $query = $this->db->query($sql);
                if($json['extension_install_id'] < 0){
                    $data['data'] = array(
                        'id' => $this->db->getLastId()
                    );
                }
                else {
                    $data['data'] = array(
                        'id' => $json['extension_install_id']
                    );
                }
            }
        }
        catch (\Exception $e){
            $data['error'] = array(
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            );
            $this->log->write($data['error']);
            $this->log->write($sql);
        }
        return $data;
    }

    public function deleteExtensionGroup(int $extension_install_id):array {
        $db_prefix = DB_PREFIX;
        $sql1 = "SELECT COUNT(`extension_install_id`) FROM `{$db_prefix}extension_path` WHERE `extension_install_id`='$extension_install_id'";
        $sql2 = "DELETE FROM `{$extension_install_id}extension_install` WHERE `extension_install_id`='$extension_install_id'";
        $data = array();
        try {
            $query = $this->db->query($sql1);
            if ($query->num_rows) {
                $data['error'] = array(
                    'code' => 1,
                    'msg' => 'msg_del_ex_group'
                );
            } else {
                $this->db->query($sql2);
            }
        }
        catch (Exception $e){
            $data['error'] = array(
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            );
            $this->log->write($data['error']);
            $this->log->write($sql1);
        }
        return $data;
    }

    public function getExtensionGroupDetail(string $code, int $extension_install_id):array {
        $data = array();
        $data['data'] = array();
        $data['check'] = array();
        $db_prefix = DB_PREFIX;
        //$sql1 = "SELECT * FROM `{$db_prefix}extension` WHERE `extension`='{$code}'";
        $sql1 = "SELECT * FROM `{$db_prefix}extension`";
        //$sql2 = "SELECT `path` FROM `{$db_prefix}extension_path` WHERE `extension_install_id`='{$extension_install_id}'";
        $sql2 = "SELECT `path` FROM `{$db_prefix}extension_path` WHERE `path` LIKE '%controller%'";
        $sql3 = "SELECT * FROM `{$db_prefix}extension_install` WHERE `extension_install_id`='{$extension_install_id}'";
        try {
            $info = array();
            $list1 = $this->db->query($sql1)->rows;
            $available_paths = $this->db->query($sql2)->rows;
            $list2 = $this->getExtensionFromFiles();
            //$list2 = $this->db->query($sql2)->rows;
            $group = $this->db->query($sql3)->row;
            foreach ($list2 as $item2){
                foreach ($list1 as $item1){
                    if($item1['code'] == $item2['code']){
                        $item2['extension_id'] = $item1['extension_id'];
                        $item2['extension'] = $item1['extension'];
                        break;
                    }
                }
                $check = $this->checkExtension($item2['path']['controller'][0]['path'], $extension_install_id, $available_paths);
                if($check == false){
                    $this->migrate($item2['code'], $item2['type'] );
                }
                $item2['installed'] = $item2['extension_id'] >= 0;
                if($item2['extension'] == '' || $item2['extension'] == $code){
                    $data['data'][] = $item2;
                }
                $data['check'][] = $item2['path']['controller'][0]['path'];

            }
            $data['extension'] = $code;
            $data['path'] = $available_paths;

            $data['detail'] = $group;
            $data['detail']['default'] = $this->isDefault($code);
            $data['detail']['types'] = $this->allExtensionType();
        }
        catch (Exception $e){
            $data['error'] = array(
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            );
            $this->log->write($data['error']);
            $this->log->write($sql1);
            $this->log->write($sql2);
        }
        return $data;
    }

    public function createExtension(array $json):array{
        $data = array();
        $db_prefix = DB_PREFIX;
        $sql1 = "SELECT * FROM `{$db_prefix}extension` WHERE `code`='{$json['code']}'";
        //$sql2 = "INSERT INTO `{$db_prefix}extension` (`extension_id`, `extension`, `type`, `code`) VALUES (NULL , '{$this->db->escape($json['extension'])}', '{$this->db->escape($json['type'])}', '{$this->db->escape($json['code'])}')";
        $sql3 = "INSERT INTO `{$db_prefix}extension_path` (`extension_path_id`, `extension_install_id`, `path`) VALUES ";
        $path = array();
        foreach ($json['paths'] as $url){
            $path[] = "(NULL, '{$json['extension_install_id']}','{$this->db->escape($url)}')";
            break;
        }
        $sql3 = $sql3.implode(',', $path);

        try {
            $num = $this->countExtensionFromFiles($json['code']);
            if($num > 0){
                $data['error'] = array(
                    'code' => 1,
                    'msg' => 'invalid_ext_code'
                );
            }
            else {
                //$this->db->query($sql2);
                //$extension_id = $this->db->getLastId();
                $this->db->query($sql3);
                $data['data'] = array(
                    'id' => -1,
                    'paths' => $this->createFile($json['paths'] , $json['code'], $json['type'], $json['name'])
                );

            }
        }
        catch (\Exception $e){
            $data['error'] = array(
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            );
            $this->log->write($data['error']);
            $this->log->write($sql1);
            $this->log->write($sql3);
        }
        return $data;
    }

    public function deleteExtension(string $code, string $type):array {
        $data = array();
        $db_prefix = DB_PREFIX;
        $path = "$type/$code";
        $sql1 = "DELETE FROM `{$db_prefix}extension` WHERE `code`='{$code}'";
        $sql2 = "DELETE FROM `{$db_prefix}extension_path` WHERE `path` LIKE '%{$code}%'";
        try {
            $data = $this->deleteFile($code, $type);
            if(array_key_exists('error', $data) == false){
                $this->db->query($sql1);
                $this->db->query($sql2);
                $data['data'] = array();
            }
        }
        catch (\Exception $e){
            $data['error'] = array(
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            );
            $this->log->write($data['error']);
            $this->log->write($sql1);
            $this->log->write($sql2);
        }
        return $data;
    }

    private function deleteFile(string $code, string $type){
        $data = array();
        $this->deleteExtensionFromFilePaths($code, $type);
        /*
        $data['path'] = array();
        try {
            $this->load->helper('file');
            $file = new FileHelper();
            $dir = DIR_EXTENSION;
            $db_prefix = DB_PREFIX;
            $path = "$type/$code";
            $sql = "SELECT * FROM `{$db_prefix}extension_path` WHERE `path` LIKE '%$code%'";
            $paths = $this->db->query($sql)->rows;
            $data['sql'] = $sql;
            foreach ($paths as $item){
                $url = $dir.$item['path'];
                $data['path'][] = $url;
                $file->delete_file($url);
            }
        }
        catch (\Exception $e){
            $data['error'] = array(
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            );
            $this->log->write($data['error']);
            $this->log->write($sql);
        }
        */
        return $data;
    }

    private function createFile(array $paths, string $code, string $type, string $name, string $extension='opencart'):array {
        $this->load->helper('file');
        $dir = DIR_EXTENSION;
        $file = new FileHelper($dir);
        $data = array();
        $type_np = ucwords($type);
        $ext = ucwords($extension);

        foreach ($paths as $path){
            $file_name = $file->file_name($path);
            $class_name = str_replace(['_', '/'], ['', '\\'], ucwords($file_name, '_/'));
            if(strpos($path, 'controller')){
                if(strpos($path, 'admin')){
                    $namespace = "$ext\Admin\Controller\Extension\\$ext\\$type_np";
                    $contents = <<<EOD
<?php
namespace $namespace;
class $class_name extends \Opencart\System\Engine\Controller {
	public function index(): void {
		\$this->load->language('extension/$extension/$type/$file_name');
		\$this->document->setTitle(\$this->language->get('heading_title'));
		\$this->load->model('extension/$extension/$type/$file_name');
		\$data['breadcrumbs'] = [];
		\$data['breadcrumbs'][] = [
			'text' => \$this->language->get('text_home'),
			'href' => \$this->url->link('common/dashboard', 'user_token=' . \$this->session->data['user_token'])
		];
		\$data['breadcrumbs'][] = [
			'text' => \$this->language->get('heading_title'),
			'href' => \$this->url->link('extension/$extension/$type/$file_name', 'user_token=' . \$this->session->data['user_token'])
		];
		\$data['header'] = \$this->load->controller('common/header');
		\$data['column_left'] = \$this->load->controller('common/column_left');
		\$data['footer'] = \$this->load->controller('common/footer');

		\$this->response->setOutput(\$this->load->view('extension/$extension/$type/$file_name', \$data));
	}

	public function install(): void {}
	public function uninstall(): void {}
}
EOD;

                }
                else {
                    $namespace = "$ext\Catalog\Controller\Extension\\$ext\\$type_np";
                    $contents = <<<EOD
<?php
namespace $namespace;
class $class_name extends \Opencart\System\Engine\Controller {
	public function index(): string {
		\$this->load->language('extension/$extension/$type/$file_name');
		\$this->load->model('extension/$extension/$type/$file_name');
		return \$this->load->view('extension/$extension/module/account', \$data);
	}
}
EOD;

                }
                $file->file_force_contents("$path", $contents);
            }
            else if(strpos($path, 'model')){
                if(strpos($path, 'admin')){
                    $namespace = "$ext\Admin\Model\Extension\\$ext\\$type_np";
                    $contents = <<<EOD
<?php
namespace $namespace;
class $class_name extends \Opencart\System\Engine\Model {
	public function install(): void {}
	public function uninstall(): void {}
}
EOD;

                }
                else {
                    $namespace = "$ext\Catalog\Model\Extension\\$ext\\$type_np";
                    $contents = <<<EOD
<?php
namespace $namespace;
class $class_name extends \Opencart\System\Engine\Model {
	
}
EOD;

                }
                $file->file_force_contents("$path", $contents);
            }
            else if(strpos($path, 'view')){
                if(strpos($path, 'admin')){
                    $contents = <<<EOD
{{ header }}{{ column_left }}
<div id="content">
	<div class="page-header">
		<div class="container-fluid">
			<div class="float-end">
				<h1>{{ heading_title }}</h1>
				<ol class="breadcrumb">
				{% for breadcrumb in breadcrumbs %}
					<li class="breadcrumb-item"><a href="{{ breadcrumb.href }}">{{ breadcrumb.text }}</a></li>
				{% endfor %}
			</ol>
			</div>
		</div>
	</div>
	<div class="container-fluid">
		<div class="card">
		</div>
	</div>
</div>
{{ footer }}
EOD;

                }
                else {
                    $contents = "<h1>{{heading_title}}</h1>";
                }
                $file->file_force_contents("$path", $contents);
            }
            else if(strpos($path, 'language')){
                if(strpos($path, 'admin')){
                    $contents = <<<EOD
<?php
// Heading
\$_['heading_title']    = '$name';

// Text
\$_['text_extension']   = 'Extensions';
\$_['text_success']     = 'Success: You have modified $name!';
\$_['text_edit']        = 'Edit $name';

// Entry
\$_['entry_status']     = 'Status';

// Error
\$_['error_permission'] = 'Warning: You do not have permission to modify $name!';
EOD;
                }
                else {
                    $contents = <<<EOD
<?php
// Heading
\$_['heading_title']     = '$name';
EOD;

                }
                $file->file_force_contents("$path", $contents);
            }
            else {
                $data[] = DIR_EXTENSION.$path;
                if(strpos($path, 'library')){
                    $contents = <<<EOD
<?php 
namespace Opencart\System\Library;

class $class_name {}
EOD;
                    $file->file_force_contents("$path", $contents);
                }
                else if(strpos($path, 'helper')){
                    $contents = <<<EOD
<?php 

EOD;
                    $file->file_force_contents("$path", $contents);
                }
                else if(strpos($path, 'config')){
                    $contents = <<<EOD
<?php 
\$_['{$code}_setting']="";
EOD;
                    $file->file_force_contents("$path", $contents);
                }
            }

        }
        return $data;
    }

    private function getExtensionFromFiles(string $extension='opencart'):array {
        $types = $this->allExtensionType();
        $data = array();
        foreach ($types as $type){
            $url = DIR_EXTENSION."$extension/admin/controller/$type/*.php";
            foreach (glob($url) as $file_name){
                $item = array(
                    'extension_id'=>-1,
                    'extension' => '',
                    'type' => $type,
                    'code' => basename($file_name, '.php'),
                    'path' => $this->getFiles(basename($file_name, '.php'), $type)
                );
                $data[] = $item;
            }
        }
        usort($data, function ($a, $b){
            return strcmp($a['code'], $b['code']);
        });
        return $data;
    }

    private function deleteExtensionFromFilePaths(string $code, string $type):bool {
        $data = array();
        $lists = array(
            "opencart/admin/controller/$type/$code.php",
            "opencart/catalog/controller/$type/$code.php",
            "opencart/admin/model/$type/$code.php",
            "opencart/catalog/model/$type/$code.php",
            "opencart/admin/view/template/$type/$code.twig",
            "opencart/catalog/view/template/$type/$code.twig",
            "opencart/system/config/$code.php",
            "opencart/system/library/$code.php",
            "opencart/system/helper/$code.php"
        );
       $dir = DIR_EXTENSION;
        foreach ($lists as $str){
            if(is_file($dir.$str) == true){
                unlink($dir.$str);
            }
        }
        return true;
    }

    private function countExtensionFromFiles(string $code):int {
         $exts = $this->getExtensionFromFiles();
         $count = 0;
         foreach ($exts as $item){
             if($item['code'] == $code){
                 $count += 1;
             }
         }
         return $count;
    }

    private function getFiles(string $code, string $type, string $extension='opencart'):array {
        $path = array();
        $path['model'] = array();
        $path['view'] = array();
        $path['controller'] = array();
        $path['language'] = array();
        $path['system'] = array();

        $codes = array(
            "$extension/admin/model/$type/",
            "$extension/catalog/model/$type/",
            "$extension/admin/view/template/$type/",
            "$extension/catalog/view/template/$type/",
            "$extension/admin/controller/$type/",
            "$extension/catalog/controller/$type/",
            "$extension/admin/language/en-gb/$type/",
            "$extension/catalog/language/en-gb/$type/",
            "config/",
            "library/"
        );

        foreach ($codes as $match) {
            if(strpos($match, 'model')){
                $url = DIR_EXTENSION.$match."$code*.php";
                foreach (glob($url) as $file_name){
                    $path['model'][] = array(
                        'path' => $match.basename($file_name)
                    );
                }
            }
            elseif(strpos($match, 'view')){
                $url = DIR_EXTENSION.$match."$code*.twig";
                foreach (glob($url) as $file_name){
                    $path['view'][] = array(
                        'path' => $match.basename($file_name)
                    );
                }
            }
            elseif(strpos($match, 'controller')){
                $url = DIR_EXTENSION.$match."$code*.php";
                foreach (glob($url) as $file_name){
                    $path['controller'][] = array(
                        'path' => $match.basename($file_name)
                    );
                }
            }
            elseif(strpos($match, 'language')){
                $url = DIR_EXTENSION.$match."$code*.php";
                foreach (glob($url) as $file_name){
                    $path['language'][] = array(
                        'path' => $match.basename($file_name)
                    );
                }
            }
            else{
                $url = DIR_SYSTEM.$match."$code*.php";
                foreach (glob($url) as $file_name){
                    $path['system'][] = array(
                        'path' => $match.basename($file_name)
                    );
                }
            }
        }
        return $path;
    }

    private function checkExtension(string $path, int $extension_install_id, array $paths):bool {
        $db_prefix = DB_PREFIX;
        $is_find = false;

        foreach ($paths as $value){
            //if(strpos($value['path'], $path)){
            if($value['path'] == $path){
                $is_find = true;
                break;
            }

        }

        if($is_find == false){
            $sql = "INSERT INTO `{$db_prefix}extension_path` (`extension_path_id`, `extension_install_id`, `path`) VALUES (NULL, '$extension_install_id', '$path')";
            $this->db->query($sql);

        }

        return $is_find;
    }

    private function migrate(string $code, string $type, string $extension='opencart') : bool {
        $this->load->helper('file');
        $dir = DIR_EXTENSION;
        $file = new FileHelper($dir);

        $code_up = ucwords($code);
        $type_up = ucwords($type);
        $ext_up = ucwords($extension);

        $paths = array(
            "$extension/admin/model/$type/$code.php",
            "$extension/catalog/model/$type/$code.php",
            "$extension/admin/controller/$type/$code.php",
            "$extension/catalog/controller/$type/$code.php"
        );

        $array1 = array(
            "$code_up\\$type_up",
            "$code/$type",
            "{$code}_{$type}"
        );

        $array2 = array(
            "$ext_up\\$type_up",
            "$extension/$type",
            "{$extension}_{$type}"
        );

        $debug = "from (".implode(',',$array1).") to (".implode(',',$array2).")";
        $this->log->write(">>> $debug");

        $classname_regexp = "/\bclass\b\s{1}[A-Za-z0-9]*\s{1}\bextends\b/";
        $namespace_regexp = '/\bExtension\b[A-Za-z0-9\\\]*\b'.$type_up.'\b/';

        foreach ($paths as $path){
            if($file->is_file_exists($path)){
                try {
                    $contents = $file->get_content_file($path);
                    $contents = str_replace($array1, $array2, $contents);
                    // Correct class name
                    $contents = preg_replace($classname_regexp, "class $code_up extends", $contents);
                    // Correct name namespace
                    $contents = preg_replace($namespace_regexp, "Extension\\$ext_up\\$type_up", $contents);
                    if($contents){
                        $file->file_force_contents($path, $contents);
                    }
                }
                catch (\Exception $e){
                    return false;
                }

            }
        }

        return true;
    }
}