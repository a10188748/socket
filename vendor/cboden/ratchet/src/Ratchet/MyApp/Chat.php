<?php
namespace Ratchet\MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
 
class Chat implements MessageComponentInterface {
    protected $clients;
 
    public function __construct() {
        // 沒有key值結構 放棄...
        // $this->clients = new \SplObjectStorage;
        $this->clients = array();
        session_start();
    }
 
    public function onOpen(ConnectionInterface $conn) {
        // $this->clients->attach($conn);
        $this->clients[$conn->resourceId] = $conn;
        echo '目前有'.count($this->clients).'個連線,新連線id為'.$conn->resourceId."\n";
        // var_dump($conn);
        // echo "New connection! ({$conn->resourceId})\n";
        $status = self::setsession($conn->resourceId);
        if($status) {
            for($i=0 ;$i<2 ;$i++) {
                $this->clients[$status[$i]]->send('連線完成');
            }
            // foreach ($this->clients as $client) {
            //     if($client->resourceId == $status[0] || $client->resourceId == $status[1])
            //         $client->send('連線完成');
            // }
        }
        print_r($_SESSION);
        // $conn->send('連線完成');
    }

    public function setsession($userid)
    {
        $i = 0;
        $pair = array();
        $_SESSION[$userid]=array('status' => 'Idle',
                                 'connto' => '',
                                );
        foreach ($_SESSION as $key => $value) {
            if($value['status'] == 'Idle') {
                $pair[$i] = $key;
                $i++;
            }
            if($i == 2) {
                $_SESSION[$pair[0]] = array('status' => 'Busy',
                                            'connto' => $pair[1],
                                            );
                $_SESSION[$pair[1]] = array('status' => 'Busy',
                                            'connto' => $pair[0],
                                            );
                return array($pair[0],$pair[1]);
            }
        }
        return false;
    }
 
    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s'."\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');
        
        foreach ($_SESSION as $id => $value) {
            if($from->resourceId == $id) {
                foreach ($this->clients as $client) {
                   if($client->resourceId == $value['connto'])
                   {
                        $client->send($msg);
                   } 
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

        $connto = $_SESSION[$conn->resourceId]['connto'];
        if($connto !="")
        $this->clients[$connto]->send('close');

        echo "Connection ".$conn->resourceId." has disconnected\n";
        echo "Connection ".$connto." has disconnected\n";

        unset($this->clients[$connto]);
        unset($this->clients[$conn->resourceId]);
        unset($_SESSION[$conn->resourceId]);
        unset($_SESSION[$connto]);
        
    }
 
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
 
        $conn->close();
    }
}
