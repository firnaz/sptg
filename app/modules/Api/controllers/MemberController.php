<?php
class Api_MemberController extends Api_Controller_Action{
	public function viewAction()
	{
		extract($this->getRequest()->getParams());
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		
		if(!$sort) { 
			$dir = 'ASC'; 
			$sort='name';
		}else{
			$order = json_decode($sort);
			$dir = $order[0]->direction;
			$sort = $order[0]->property;
		}
		
		$member = new Member();
		$data = $member->fetchAll($member->select()->from($member, array('id'=>'name', 'username'=>'name','enabled','nama_lengkap','instansi','alamat_instansi','jenis_kelamin','foto_filename','foto_file_renamed','num_layer_uploaded','num_peta_created'))->order("$sort $dir")->limit($limit,$start));

		$total = $db->fetchRow($db->select()->from(array('users'),array('count(*) as total')));
		$data = count($data)?$data->toArray():array();
	
		
		echo json_encode(array("rows"=>$data, "total"=>$total["total"]));	
	}
	public function addAction(){
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());

		$member = new Member();
		$memberroles = new Memberroles();

		$checkData = $member->fetchAll($member->select()->where("name='$username'"));
		if (count($checkData)==0){
			$uploaded = $this->uploadFoto($username);

			if(is_array($uploaded)){
				$foto_filename = $uploaded["file"];
				$foto_file_renamed = $uploaded["file_renamed"];
			}else{
				$foto_filename     = null;
				$foto_file_renamed = null;
			}
			$row  = $member->createRow();
			$row->name          	 = $username;
			$row->password           = "plain:".md5($password);
			$row->nama_lengkap       = $nama_lengkap;
			$row->enabled           = $enabled;
			$row->instansi           = $instansi;
			$row->alamat_instansi    = $alamat_instansi;
			$row->jenis_kelamin      = $jenis_kelamin;
			$row->foto_filename      = $foto_filename;
			$row->foto_file_renamed  = $foto_file_renamed;
			$row->num_layer_uploaded = 0;
			$row->num_peta_created   = 0;
			$row->save();

			$row  = $memberroles->createRow();
			$row->username = $username;
			$row->rolename = "SIPITUNG";
			$row->save();

			$result["success"] = "true";
		}else{
			$result["success"] = "false";
			$result["reason"] = "Member dengan username $username sudah ada.";
		}
		echo Zend_Json::encode($result);
	}
	function editAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());
		$member = new Member();
		$memberroles = new Memberroles();

		$checkData = $member->fetchAll($member->select()->where("name='$id'"));
		if (count($checkData)==0 || $username==$id){

			$data['name']               =$username;
			if(trim($password)){
				$data['password']           ="plain:".md5($password);
			}
			$data['enabled']            =$enabled;
			$data['nama_lengkap']       =$nama_lengkap;
			$data['instansi']           =$instansi;
			$data['alamat_instansi']    =$alamat_instansi;
			$data['jenis_kelamin']      =$jenis_kelamin;
			$uploaded = $this->uploadFoto($username);

			if(is_array($uploaded)){
				$foto_filename = $uploaded["file"];
				$foto_file_renamed = $uploaded["file_renamed"];
			}else{
				$foto_filename     = null;
				$foto_file_renamed = null;
			}

			$data['foto_filename']      =$foto_filename;
			$data['foto_file_renamed']  =$foto_file_renamed;

			$where = $member->getAdapter()->quoteInto('name= ?', $id);
			$member->update($data, $where);

			$where = $memberroles->getAdapter()->quoteInto('username= ?', $id);
			$memberroles->delete($where);

			$row  = $memberroles->createRow();
			$row->username = $username;
			$row->rolename = "SIPITUNG";
			$row->save();

			$result["success"] = "true";
		}else{
			$result["success"] = "false";
			$result["reason"] = "Member dengan username $username sudah ada.";
		}
		echo json_encode($result);
	}
	public function deleteAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		extract($this->getRequest()->getParams());
		
		$member = new Member();
		$memberroles = new Memberroles();
		$checkData = $member->fetchAll($member->select()->where("name='$id'"));
		@unlink(FOTO_MEMBER."/".$checkData->foto_file_renamed);
		
		$where = $member->getAdapter()->quoteInto('name= ?', $id);
		$member->delete($where);
		$where = $memberroles->getAdapter()->quoteInto('username= ?', $id);
		$memberroles->delete($where);

		$result["success"] = "true";
		echo json_encode($result);
	}
	public function uploadFoto($username){
		$upload = new Zend_File_Transfer_Adapter_Http();

		$upload->setDestination(FOTO_MEMBER)->addValidator('Extension', false, array('jpg', 'png', 'gif'));
		
		$files  = $upload->getFileInfo();

		foreach($files as $file=>$info){
			if ($upload->isUploaded($file)) {
				if ($upload->isValid($file)) {
					$nama_file = basename($upload->getFileName($file));
					$file_ext = pathinfo($info['name'], PATHINFO_EXTENSION);
					$nama_file_renamed = $username.".".$file_ext;
					$upload->addFilter(new Zend_Filter_File_Rename(array('target' => FOTO_MEMBER."/".$nama_file_renamed, 'overwrite' => true)), null, $file);
					if($upload->receive($file)){
						return array("file_renamed"=>$nama_file_renamed, "file"=>$nama_file);
					}
				}
			}
		}
		return false;
	}
}
?>