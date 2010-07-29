<?php if(!isset($_GET['demo'])){/*
  Simple PHP demo
*/?><?php
  $myname = 'Chris';
  echo '<p>This is PHP!</p>';
  echo "<p>My name is $myname</p>";
  echo '<p>My name in another notation is still  '.$myname.'</p>';
?><?php }?><?php if($_GET['demo']=='1'){/*
  Showing the interaction between PHP and HTML
*/?><?php
  $origin = 'Outer Space';
  $planet = 'Earth';
  $plan = 9;
  $sceneryType = "awful";
?>
<h1>Synopsis</h1>

<p>It was a peaceful time on planet <?php echo $planet;?> 
and people in the <?php echo $sceneryType;?> scenery were unaware 
of the diabolic plan <?php echo $plan;?> from <?php echo $origin;?> 
that will take their senses to the edge of what can be endured.</p>
<?php }?><?php if($_GET['demo']=='2'){/*
  Using print_r() for debugging
*/?><?php
$lampstack = array('Linux','Apache','MySQL','PHP');
print_r($lampstack);
?><?php }?><?php if($_GET['demo']=='3'){/*
  Accessing array members
*/?><ul><?php
$lampstack = array('Linux','Apache','MySQL','PHP');
echo '<li>Operating System:'.$lampstack[0] . '</li>';
echo '<li>Server:' . $lampstack[1] . '</li>';
echo '<li>Database:' . $lampstack[2] . '</li>';
echo '<li>Language:' . $lampstack[3] . '</li>';
?></ul><?php }?><?php if($_GET['demo']=='4'){/*
  Looping over arrays
*/?><ul><?php
$lampstack = array('Linux','Apache','MySQL','PHP');
$labels = array('Operating System','Server','Database','Language');
$length = sizeof($lampstack);
for( $i = 0;$i < $length;$i++ ){
  echo '<li>' . $labels[$i] . ':' . $lampstack[$i] . '</li>';
}
?></ul><?php }?><?php if($_GET['demo']=='5'){/*
  Using array keys and several arrays
*/?><ul><?php
$lampstack = array(
  'Operating System' => 'Linux',
  'Server' => 'Apache',
  'Database' => 'MySQL',
  'Language' => 'PHP'
);
$length = sizeof($lampstack);
$keys = array_keys($lampstack);
for( $i = 0;$i < $length;$i++ ){
  echo '<li>' . $keys[$i] . ':' . $lampstack[$keys[$i]] . '</li>';
}
?></ul><?php }?><?php if($_GET['demo']=='6'){/*
  Looping over an associative array
*/?><ul><?php
$lampstack = array(
  'Operating System' => 'Linux',
  'Server' => 'Apache',
  'Database' => 'MySQL',
  'Language' => 'PHP'
);
foreach( $lampstack as $key => $stackelm ){
  echo '<li>' . $key . ':' . $stackelm . '</li>';
}
?>
</ul><?php }?><?php if($_GET['demo']=='7'){/*
  Using foreach
*/?><ul><?php
$lampstack = array(
  'Operating System' => 'Linux',
  'Server' => 'Apache',
  'Database' => 'MySQL',
  'Language' => 'PHP'
);
if( sizeof($lampstack) > 0 ){
  foreach( $lampstack as $key => $stackelm ){
    echo '<li>' . $key . ':' . $stackelm . '</li>';
  }
}
?>
</ul>
<?php }?><?php if($_GET['demo']=='8'){/*
  Writing a reusable function
*/?><?php
function renderList($array){
  if( sizeof($array) > 0 ){
    echo '<ul>';
    foreach( $array as $key => $item ){
      echo '<li>' . $key . ':' . $item . '</li>';
    }
    echo '</ul>';
  }
}
$lampstack = array(
  'Operating System' => 'Linux',
  'Server' => 'Apache',
  'Database' => 'MySQL',
  'Language' => 'PHP'
);
renderList($lampstack);

$awfulacting = array(
  'Natalie Portman' => 'Star Wars',
  'Arnold Schwarzenegger' => 'Batman and Robin',
  'Keanu Reaves' => '*'
);
renderList($awfulacting);
?><?php }?><?php if($_GET['demo']=='9'){/*
  Using $_GET parameters
*/?><?php
$name = 'Chris';
// if there is no language defined, switch to English
if( !isset($_GET['language']) ){
  $welcome = 'Oh, hello there, ';
}
if( $_GET['language'] == 'fr' )
  $welcome = 'Salut, ';

switch($_GET['font']){
  case 'small':
    $size = 80;
  break;
  case 'medium':
    $size = 100;
  break;
  case 'large':
    $size = 120;
  break;
  default:
    $size = 100;
  break;
}
echo '<style>body{font-size:' . $size . '%;}</style>';
echo '<h1>'.$welcome.$name.'</h1>';
?><?php }?><?php if($_GET['demo']=='10'){/*
  Parameter filtering
*/?><?php
  $search_html = filter_input(INPUT_GET, 's', FILTER_SANITIZE_SPECIAL_CHARS);
  $search_url = filter_input(INPUT_GET, 's', FILTER_SANITIZE_ENCODED);
?>
<form action="index.php" method="get">
  <div>
    <label for="search">Search:</label>
    <input type="text" name="s" id="search" 
           value="<?php echo $search_html;?>">
   <input type="hidden" name="demo" value="10">
   <input type="submit" value="Make it so">
  </div>
</form>
<?php
if(isset($_GET['s'])){
  echo '<h2>You searched for '.$search_html.'</h2>';
  echo '<p><a href="index.php?search='.$search_url.'">Search again.</a></p>';
}?><?php }?><?php if($_GET['demo']=='11'){/*
  Loading HTML
*/?><?php
  // define the URL to load
  $url = 'http://www.smashingmagazine.com';
  // start cURL
  $ch = curl_init(); 
  // tell cURL what the URL is
  curl_setopt($ch, CURLOPT_URL, $url); 
  // tell cURL that you want the data back from that URL
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
  // run cURL
  $output = curl_exec($ch); 
  // end the cURL call (this also cleans up memory so it is 
  // important)
  curl_close($ch);
  // display the output
  echo $output;
?><?php }?><?php if($_GET['demo']=='12'){/*
  Loading and filtering HTML
*/?><?php
  // define the URL to load
  $url = 'http://www.smashingmagazine.com';
  // start cURL
  $ch = curl_init(); 
  // tell cURL what the URL is
  curl_setopt($ch, CURLOPT_URL, $url); 
  // tell cURL that you want the data back from that URL
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
  // run cURL
  $output = curl_exec($ch); 
  // end the cURL call (this also cleans up memory so it is 
  // important)
  curl_close($ch);
  // if a filter parameter with the value links was sent
  if($_GET['filter'] == 'links'){
    // get all the links from the document and show them
    echo '<ul>';
    preg_match_all('/<a[^>]+>[^<\/a>]+<\/a>/msi',$output,$links);
    foreach($links[0] as $l){
      echo '<li>' . $l . '</li>';      
    }
    echo'</ul>';
  // otherwise just show the page
  } else {
    echo $output;
  }
?><?php }?><?php if($_GET['demo']=='13'){/*
  Loading and displaying XML
*/?><?php
  $url = 'http://rss1.smashingmagazine.com/feed/';
  $ch = curl_init(); 
  curl_setopt($ch, CURLOPT_URL, $url); 
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
  $output = curl_exec($ch); 
  curl_close($ch);
  $data = simplexml_load_string($output);
  echo '<ul>';
  foreach($data->entry as $e){
    echo '<li><a href="'.$e->link[0]['href'].'">'.$e->title.'</a></li>';
  }
  echo '</ul>';
?><?php }?><?php if($_GET['demo']=='14'){/*
  JSON example
*/?><?php
  $json = '{
  "lampstack":
  {
    "operatingsystem":"Linux",
    "server":"Apache",
    "database":"MySQL",
    "language":"PHP"
    }
  }';
  print_r(json_decode($json));
?>
<?php }?><?php if($_GET['demo']=='15'){/*
  JSON API example
*/?><pre><?php
  $url = 'http://search.twitter.com/trends.json';
  $ch = curl_init(); 
  curl_setopt($ch, CURLOPT_URL, $url); 
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
  $output = curl_exec($ch); 
  curl_close($ch);
  $data = json_decode($output);
  print_r($data);
?></pre>
<?php }?><?php if($_GET['demo']=='16'){/*
  Format JSON API results example
*/?><?php
  $url = 'http://search.twitter.com/trends.json';
  $ch = curl_init(); 
  curl_setopt($ch, CURLOPT_URL, $url); 
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
  $output = curl_exec($ch); 
  curl_close($ch);
  $data = json_decode($output);
  echo '<h2>Twitter trending topics ('.$data->as_of.')</h2>';
  echo '<ul>';
  foreach ($data->trends as $t){
    echo '<li><a href="'.$t->url.'">'.$t->name.'</a></li>';
  }
  echo '</ul>';
?>
<?php }?><?php if($_GET['demo']=='17'){/*
  Web search example
*/?><?php
  $search_html = filter_input(INPUT_GET, 's', FILTER_SANITIZE_SPECIAL_CHARS);
  $search_url = filter_input(INPUT_GET, 's', FILTER_SANITIZE_ENCODED);
?>
<form action="index.php" method="get">
  <div>
    <label for="search">Search:</label>
    <input type="text" name="s" id="search" 
           value="<?php echo $search_html;?>">
   <input type="hidden" name="demo" value="17">
   <input type="submit" value="Make it so">
  </div>
</form>
<?php
if(isset($_GET['s'])){
  echo '<h2>You searched for '.$search_html.'</h2>';
  $yql = 'select * from search.web where query="'.$search_url.'"';
  $url = 'http://query.yahooapis.com/v1/public/yql?q='.
          urlencode($yql).'&format=json&diagnostics=false';
  $ch = curl_init(); 
  curl_setopt($ch, CURLOPT_URL, $url); 
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
  $output = curl_exec($ch); 
  curl_close($ch);
  $data = json_decode($output);
  echo '<ul>';
  foreach ($data->query->results->result as $r){
    echo '<li><h3><a href="'.$r->clickurl.'">'.$r->title.'</a></h3>'.
         '<p>'.$r->abstract.' <span>('.$r->dispurl.')</span></p></li>';
  }
  echo '</ul>';

  echo '<p><a href="index.php?search='.$search_url.'&demo=17">Search again.</a></p>';
}
?>
<?php }?><?php if($_GET['demo']=='18'){/*
  Loading XML and converting it to JavaScript
*/?><?php
  header('Content-type: text/javascript');
  $url = 'http://rss1.smashingmagazine.com/feed/';
  $ch = curl_init(); 
  curl_setopt($ch, CURLOPT_URL, $url); 
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
  $output = curl_exec($ch); 
  curl_close($ch);
  $data = simplexml_load_string($output);
  $data = json_encode($data);
  echo 'var smashingrss='.$data;
?>
<?php }?><?php if($_GET['demo']=='19'){/*
  Including XML as JavaScript
*/?><?php
  echo '<script src="http://icant.co.uk/articles/phpforhacks/index.php?demo=18"></script>';
  echo '<script>alert(smashingrss.title);</script>';
?>
<?php }?>