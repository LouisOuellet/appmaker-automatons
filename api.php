<?php
class automatonsAPI extends CRUDAPI {
	public function read($request = null, $data = null){
		if(isset($data)){
			if(!is_array($data)){ $data = json_decode($data, true); }
			$this->Auth->setLimit(0);
			return parent::read($request, $data);
		}
	}
	public function mail_to_api($request = null, $data = null){
		if(isset($data)){
			if(!is_array($data)){ $data = json_decode($data, true); }
			$return = [];
			if(isset($data['imap']['host'],$data['imap']['port'],$data['imap']['encryption'],$data['imap']['username'],$data['imap']['password'],$data['request'],$data['type'],$data['headers'])){
				// Setup Mailbox
				$IMAP = new PHPIMAP($data['imap']['host'],$data['imap']['port'],$data['imap']['encryption'],$data['imap']['username'],$data['imap']['password']);
				if($IMAP->Box != null){
					if($IMAP->Meta->Recent > 0){
						foreach($IMAP->NewMSG as $msg){
							if($msg->Body->HTML != ""){ $body = $msg->Body->Unquoted; }
							else { $body = $msg->Body->PLAIN; }
							$dbRec = [];
							foreach($data['headers'] as $msgHDR => $dbHDR){
								switch($msgHDR){
									case"subject": $dbRec[$dbHDR] = $msg->Subject->PLAIN; break;
									case"body": $dbRec[$dbHDR] = $body; break;
									case"from": $dbRec[$dbHDR] = $msg->from[0]->mailbox."@".$msg->from[0]->host; break;
									default: $dbRec[$dbHDR] = $msg->$msgHDR; break;
								}
							}
							$APIFile = dirname(__FILE__,3)."/plugins/".$data['request']."/api.php";
							if(file_exists($APIFile)){
								require_once $APIFile;
								if(class_exists($data['request'].'API')){
									$API = $data['request'].'API';
									$API = new $API();
									if(method_exists($API,$data['type'])){
										$method = $data['type'];
										array_push($return,$API->$method($data['request'], ["record" => $dbRec, "msg" => (array) $msg]));
										imap_setflag_full($IMAP->Box, $msg->Msgno, "\\Seen \\Flagged");
									} else { echo "Method: ".$data['type']." does not exists\n"; }
								} else { echo "Class: ".$data['request']."API does not exists\n"; }
							} else { echo "File: ".$APIFile." does not exists\n"; }
						}
					}
				}
			}
			return $return;
		}
	}

	public function MailtoDB(){
		if(isset($data)){
			if(!is_array($data)){ $data = json_decode($data, true); }
			if(isset($data['imap']['host'],$data['imap']['port'],$data['imap']['encryption'],$data['imap']['username'],$data['imap']['password'],$data['request'],$data['type'],$data['headers'])){
				// Init IMAP
		    $IMAP = new PHPIMAP($data['imap']['host'],$data['imap']['port'],$data['imap']['encryption'],$data['imap']['username'],$data['imap']['password']);
		    // Check Connection Status
		    if($IMAP->isConnected()){
		      // Retrieve INBOX
		      $inbox = $IMAP->get();
		      // Output ids and subject of all messages retrieved
		      foreach($inbox->messages as $msg){
		        $message = [
		          "account" => $data['imap']['username'],
		          "folder" => "INBOX",
		          "mid" => $msg->Header->message_id,
		          "uid" => $msg->UID,
		          "reply_to_id" => "",
		          "reference_id" => "",
		          "sender" => $msg->Sender,
		          "from" => $msg->From,
		          "to" => "",
		          "cc" => "",
		          "bcc" => "",
		          "meta" => "",
		          "subject_original" => $msg->Subject->Full,
		          "subject_stripped" => $msg->Subject->PLAIN,
		          "body_original" => $msg->Body->Content,
		          "body_unquoted" => $msg->Body->Unquoted,
		          "attachments" => "",
		        ];
		        if(isset($msg->Header->in_reply_to)){ $message["reply_to_id"] = $msg->Header->in_reply_to; }
		        if(isset($msg->Header->references)){
		          foreach(explode(' ',$msg->Header->references) as $reference){
		            $message["reference_id"] .= trim($reference,',').";";
		          }
		          $message["reference_id"] = trim($message["reference_id"],';');
		        }
		        foreach($msg->To as $to){
		          $message["to"] .= $to.";";
		        }
		        $message["to"] = trim($message["to"],';');
		        foreach($msg->CC as $cc){
		          $message["cc"] .= $cc.";";
		        }
		        $message["cc"] = trim($message["cc"],';');
		        foreach($msg->BCC as $bcc){
		          $message["bcc"] .= $bcc.";";
		        }
		        $message["bcc"] = trim($message["bcc"],';');
		        foreach($msg->Attachments->Files as $file){
		          $file["created"] = date("Y-m-d H:i:s");
		          $file["modified"] = date("Y-m-d H:i:s");
		          $file["owner"] = $this->Auth->User['id'];
		          $file["updated_by"] = $this->Auth->User['id'];
		          $file["isAttachment"] = "true";
		          $file["file"] = $file["attachment"];
		          $file["size"] = $file["bytes"];
		          $file["encoding"] = $file["encoding"];
		          $file["meta"] = "";
		          $file["type"] = "unknown";
		          if(isset($file["name"])){
		            $filename = explode('.',$file["name"]);
		            $file["type"] = end($filename);
		          } else { $file["name"] = null; }
		          if(isset($file["filename"])){
		            $filename = explode('.',$file["filename"]);
		            $file["type"] = end($filename);
		          } else { $file["filename"] = null; }
		          $message["attachments"] .= $this->saveFile($file).";";
		        }
		        $message["attachments"] = trim($message["attachments"],';');
		        $message["created"] = date("Y-m-d H:i:s");
		        $message["modified"] = date("Y-m-d H:i:s");
		        $message["owner"] = $this->Auth->User['id'];
		        $message["updated_by"] = $this->Auth->User['id'];
		        $this->saveMail($message);
		        $IMAP->delete($message['uid']);
		      }
		    }
			}
		}
	}

	public function mail_to_mail($request = null, $data = null){
		if(isset($data)){
			if(!is_array($data)){ $data = json_decode($data, true); }
		}
	}
	public function db_to_mail($request = null, $data = null){
		if(isset($data)){
			if(!is_array($data)){ $data = json_decode($data, true); }
		}
	}
}
