<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">  
  <title>Demo of showing non-authenticated YQL data with PHP and cURL</title>
  <link rel="stylesheet" type="text/css" href="../yui.css">
  <link rel="stylesheet" type="text/css" href="../styles.css">
  <style type="text/css" media="screen">
    pre{overflow:auto;padding:1em;background:#eee;border:1px solid #999;}
  </style>
</head>
<body class="yui-skin-sam">
<div id="doc" class="yui-t7">
  <div id="hd" role="banner">
    <h1>Demo of showing non-authenticated YQL data with PHP and cURL</h1>
    <p><a href="../index.html">Back to index</a></p>
  </div>
  <div id="bd" role="main">
    <?php
    // Your YQL query 
    $query = 'select * from search.web where query="YQL"';

    // start the URL by defining the API endpoint and encoding the query
    $apiendpoint = 'http://query.yahooapis.com/v1/public/yql?q=';
    $url = $apiendpoint . urlencode($query);

    // diagnostics - remove if you don't need them
    $url .= '&diagnostics=true';

    // format (json or xml)
    $url .= '&format=json'; 

    // environment. this gives you access to the community tables
    $url .= '&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys';

    // initiate curl request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
    $results = curl_exec($ch);
    
    echo '<h2>Output</h2>';
    $data = json_decode($results);
    if($data->query->results){
      echo '<ol>';
      foreach($data->query->results->result as $r){
        echo '<li><h3><a href="'.$r->clickurl.'">'.$r->title.'</a></h3>'.
             '<p>'.$r->abstract.' ('.$r->dispurl.')</p></li>';
      }
      echo '</ol>';
    } else {
      echo '<h2>Debugging information</h2>';
      echo '<h3>URL</h3>';
      echo '<p><a href="'.$url.'">'.$url.'</a></p>';
      echo '<h3>Raw data</h3><pre>';
      echo $results;
      echo '</pre>';
      echo '<h3>Decoded data</h3><pre>';
      print_r(json_decode($results));
      echo '</pre>';
    }
    ?>
  </div>
  <div id="ft" role="contentinfo">
    <p>Part of the Hackday Toolbox</p>
  </div>
</div>
</body>
</html>