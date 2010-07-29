<?php
 include_once("keys.php");
 if(CONSUMER_KEY == '' or CONSUMER_SECRET == ''){
   die('This example needs a comsumer key and secret. Start a new project at <a href="http://developer.apps.yahoo.com/dashboard/">http://developer.apps.yahoo.com/dashboard/</a> to get yours and edit the keys.php file.');
 }
 include_once("lib/Yahoo.inc");

// Enable debugging. Errors are reported to Web server's error log.
 YahooLogger::setDebug(true);

 // Initializes session and redirects user to Yahoo! to sign in and 
 // then authorize app
 $yahoo_session = YahooSession::requireSession(CONSUMER_KEY, CONSUMER_SECRET);
 if ($yahoo_session == NULL) {
     fatal_error("yahoo_session");
 }

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">  
  <title>Demo of accessing the Yahoo Firehose with oAuth (searching for iPAd)</title>
  <link rel="stylesheet" type="text/css" href="../yui.css">
  <link rel="stylesheet" href="../styles.css" type="text/css">
  <style>
    ul{margin:0;padding:0;}
    ul li{
      list-style:none;padding-bottom:.5em;overflow:auto;margin:1em 0;
      background:#ddd;
      -moz-border-radius:10px;
      padding:10px;
      position:relative;
    }
    p{padding-left:80px;}
    li img{display:block;position:absolute;top:1em;left:1em;}
    #bd p.via{font-size:80%;text-align:right;}
    pre{overflow:auto;padding:1em;background:#eee;border:1px solid #999;}
  </style>
</head>
<body class="yui-skin-sam">
<div id="doc" class="yui-t7">
  <div id="hd" role="banner">
    <h1>Demo of accessing the Yahoo Firehose with oAuth (searching for iPAd)</h1>
    <p><a href="../index.html">Back to index</a></p>
  </div>
  <div id="bd" role="main">
  <?php
  $firehose = 'select * from social.updates.search(50) where query="ipad"';

  $data = $yahoo_session->query($firehose);

  echo '<ul>';
  foreach($data->query->results->update as $u){
    echo '<li><p><a href="' . $u->profile_uri . 
         '"><img src="' . $u->profile_displayImage . '" alt="' . 
          $u->profile_nickname . '"></a><a href="' . $u->link . '">' .
          $u->title . '</a></p><p>' . $u->description . 
          '</p><p class="via">via ' . $u->loc_localizedName . '</p></li>'; 
  }
  echo '</ul>';

  echo '<h2>Raw info</h2><pre>';
  print_r($data);
  echo '</pre>';
  
?>
  </div>
  <div id="ft" role="contentinfo">
    <p>Part of the Hackday Toolbox</p>
  </div>
</div>
</body>
</html>
