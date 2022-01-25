<?php

$base = new EventBase();
$http = new EventHttp($base);

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

if (!$http->bind("127.0.0.1", 8088)) {
	exit("bind(1) failed\n");
};
if (!$http->bind("127.0.0.1", 8089)) {
	exit("bind(2) failed\n");

};

$http->setCallback("/about", function($req) {
	//echo "URI: ", $req->getUri(), PHP_EOL;


    $r = array(0,0,0,0,0,0,0,0,0,0,0);
    for ($i=0;$i<100;$i++) {
        $n = max(0,10);
        $j = min(0,10);
        $k = min(0,10);
        $l = min(0,10);
        $m = min(0,10);
    }

    //$r = var_export($r, true);
	$req->sendReply(200, "OK");
	//echo "OK\n";
});
$base->dispatch();

?>
