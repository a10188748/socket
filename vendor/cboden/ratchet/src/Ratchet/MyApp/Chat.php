<?php
namespace Ratchet\MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
 
class Chat implements MessageComponentInterface {
    protected $clients;
    protected $message;
 
    public function __construct() {
        // 沒有key值結構 放棄...
        // $this->clients = new \SplObjectStorage;
        $this->clients = array();
        $this->message = array();
        session_start();
    }
 
    public function onOpen(ConnectionInterface $conn) {
        // 重複登入teg
        $type = false;
        // 檢查重複連線
        foreach ($_SESSION as $key => $value) {
            if($value['ip'] == $conn->remoteAddress)
            {
                $conn->send('same');
                $type = true;
            }
        }
        if(!$type)
        {
            $this->clients[$conn->resourceId] = $conn;
            echo '目前有'.count($this->clients).'個連線,新連線id為'.$conn->resourceId."\n";
            $status = self::setsession($conn->resourceId,$conn->remoteAddress);
            if($status) {
                for($i=0 ;$i<2 ;$i++) {
                    $this->clients[$status[$i]['id']]->send('連線完成');
                }
            }
            print_r($_SESSION);
            // $conn->send('連線完成');
        }
    }

    public function setsession($userid,$userip)
    {

        $i = 0;
        // 閒置的人
        $pair = array();
        foreach ($_SESSION as $key => $value) {
            if($value['ip'] == $userip)
            {
                $this->clients[$userid]->send('same');
                unset($this->clients[$userid]);
                return false;
            }
            // 若你的ip在他人的connip裡
            if($value['connip'] == $userip) {
                // 改他人的connid
                $_SESSION[$key]['connid'] = $userid;

                // 新增自己的session
                $_SESSION[$userid] = array('status' => 'Busy',
                                           'connid' => $key,
                                           'ip' => $userip,
                                           'connip' => $value['ip'],
                                          );
                $this->clients[$userid]->send('連線完成');

                // 發送暫存訊息
                if(isset($this->message[$userip])) {
                    foreach ($this->message[$userip] as $key => $value) {
                        $this->clients[$userid]->send($value);
                    }
                    unset($this->message[$userip]);
                }  
                return false;
            }
        }
        $_SESSION[$userid] = array('status' => 'Idle',
                                   'connid' => '',
                                   'ip' => $userip,
                                   'connip' =>'',
                                  );
        foreach ($_SESSION as $key => $value) {
            if($value['status'] == 'Idle') {
                $pair[$i]['id'] = $key;
                $pair[$i]['ip'] = $value['ip'];
                $i++;
            }
            if($i == 2 ) {
                $_SESSION[$pair[0]['id']] = array('status' => 'Busy',
                                            'connid' => $pair[1]['id'],
                                            'ip' => $pair[0]['ip'],
                                            'connip' => $pair[1]['ip'],
                                            );
                $_SESSION[$pair[1]['id']] = array('status' => 'Busy',
                                            'connid' => $pair[0]['id'],
                                            'ip' => $pair[1]['ip'],
                                            'connip' => $pair[0]['ip'],
                                            );
                return array($pair[0],$pair[1]);
            }
        }
        return false;
    }
 
    public function onMessage(ConnectionInterface $from, $msg) {
        
        if($msg == "close"){
            // 清掉自己的連線&session
            unset($this->clients[$from->resourceId]);
            unset($_SESSION[$from->resourceId]);

            foreach ($_SESSION as $key => $value) {
                if($value['connid'] == $from->resourceId) {

                    // 發送close消息給對象
                    $this->clients[$key]->send('close');
                    // 清掉對象的連線&session
                    unset($this->clients[$key]);
                    unset($_SESSION[$key]);

                }
            }
            print_r($_SESSION);
        }
        else{
            foreach ($_SESSION as $id => $value) {
            // 找出發送人的session
            if($from->resourceId == $id) {
                // 若發送人connid為空 [對象暫時離線 存訊息]
                if($value['connid'] == ""){
                    $this->message[$value['connip']][] = $msg;
                    print_r($this->message);
                    break;
                }
                // 若不為空 則發訊息
                else
                {
                    foreach ($this->clients as $client) {
                    // 找出發送人的對象 
                       if($client->resourceId == $value['connid']) {
                            $client->send($msg);
                            break;
                       }
                    }
                }
                
                break;
            }
        }
        }
        
        // foreach ($this->clients as $client) {
            // print_r($client->resourceId);
            // $client = $this->$this->clients[{{insert client id here}}];
            // if ($from !== $client) {
                // The sender is not the receiver, send to each client connected
                // $client->send($msg);
            // }
        // }
    }
 
    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        
        
        // $this->clients->detach($conn);
        // foreach ($this->clients as $client) {
        //     $connto = $_SESSION[$conn->resourceId]['connto'];
        //     if($client->resourceId == $connto)
        //     {
        //         $client->send('close');
        //         $this->clients->detach($client);
        //         unset($_SESSION[$connto]);
        //         echo "Connection {$client->resourceId} has disconnected\n";
        //     } 
        // }
        if(empty($_SESSION[$conn->resourceId]))return;

        // $connto = $_SESSION[$conn->resourceId]['connid'];
        // if($connto !="")
        // $this->clients[$connto]->send('close');

        echo "Connection ".$conn->resourceId." has disconnected\n";
        // echo "Connection ".$connto." has disconnected\n";

        // 準備清除對象的connid
        foreach ($_SESSION as $key => $value) {
            if($value['connid'] == $conn->resourceId) {
                $_SESSION[$key]['connid'] = "";
            }
        }
        // unset($this->clients[$connto]);
        unset($this->clients[$conn->resourceId]);
        unset($_SESSION[$conn->resourceId]);
        print_r($_SESSION);
        // unset($_SESSION[$connto]);
        
    }
 
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
 
        $conn->close();
    }
}
