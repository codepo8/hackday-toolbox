<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">  
  <title>Demo of using JavaScript in a YQL open table</title>
  <link rel="stylesheet" type="text/css" href="../yui.css">
  <link rel="stylesheet" href="../styles.css" type="text/css">
  <script type="text/javascript" src="http://yui.yahooapis.com/combo?3.2.0pr1/build/yui/yui-min.js"></script>   
  <style type="text/css">
    form{margin:1em 0;padding:.5em;background:#666;color:#fff;}
    label{padding-right:1em;}
    input{margin-right:1em;}
    #result{margin:1em 0;background:#090;padding:1em;font-weight:bold;}
    .hidden{position:absolute;left:-9999px;}
    #result.loading{background:#ccc;}
    #bd a{color:#369;}
    pre{overflow:auto;padding:1em;margin:1em 0;background:#eee;border:1px solid #999;}
  </style>
</head>
<body class="yui-skin-sam">
<div id="doc" class="yui-t7">
  <div id="hd" role="banner">
    <h1>Demo of using JavaScript in a YQL open table</h1>
    <p><a href="../index.html">Back to index</a></p>
  </div>
  <div id="bd" role="main">
  <p>Using server-side JavaScript in a YQL open table you can shift JavaScript logic to the server side. In this example I wanted to write a script that loads an HTML document and counts the amount of times a certain tag is used. With pure JavaScript this can't be done because of cross-domain loading issues. This is why I wrote the following <a href="http://isithackday.com/hackday-toolbox/yql-open-table-with-js/count-tags.xml">XML document</a> and put it on my server:</p>
<pre><code><?php 
$xml=file_get_contents('count-tags.xml');
echo htmlspecialchars($xml);
?></code></pre>
<p>The <code>y.rest()</code> command loads external data for me on the server and then allows me to manipulate it in JavaScript. In this case, I get the content and split it at the tag I want to count. Then I return an object which is all the data as plain XML (as YQL supports E4X).</p>
<p>In YQL, you can apply this table with the use command:</p>
<pre><code>use "http://isithackday.com/hackday-toolbox/yql-open-table-with-js/count-tags.xml" as count;
select * from count where url="http://www.korea-dpr.com/" and tag="strong"</code></pre>

<p>The results would be:</p>

<pre><code><?php 
$xml=file_get_contents('results.xml');
echo htmlspecialchars($xml);
?></code></pre>

<p>See the source of this page to check how to use this in YUI3. Find out more about <a href="http://developer.yahoo.com/yql/guide/yql-execute-chapter.html">server-side JavaScript in YQL here</a>.</p>

  </div>
  <div id="ft" role="contentinfo">
    <p>Part of the Hackday Toolbox</p>
  </div>
</div>
<script>
YUI().use('node','yql', function(Y){

  // add all the HTML we need for the functionality to #bd
  Y.one('#bd').prepend(''+
    '<h2>Count the tags in a certain document</h2>'+
    '<form>'+
    '<label for="url">URL:</label>'+
    '<input type="text" id="url" value="http://www.korea-dpr.com/">'+
    '<label for="tag">Tag:</label>'+
    '<input type="text" id="tag" value="strong">'+
    '<input type="submit" value="count them!">'+
    '</form><div id="result" class="hidden"></div>'+
  '');

  // define the output container 
  var container = Y.one('#result');

  // apply the event handling to the form, overriding the submit
  Y.one('form').on('submit',function(e){
    e.preventDefault();

    // get the values in the form and check that they were sent
    var url = Y.one('#url').get('value');
    var tag = Y.one('#tag').get('value');
    if(url !== '' && tag !== ''){
      // show the loading message
      container.removeClass('hidden').set('innerHTML','Loading&hellip;').
                addClass('loading');
      // call the YQL function
      get(url,tag);
    }
  })
  function get(url,tag){
    new Y.YQL('use "http://isithackday.com/hackday-toolbox/yql-open-table-with-js/count-tags.xml" as count;select * from count where url="'+url+'" and tag="'+tag+'"',     
      function(r){
        container.removeClass('loading');
        if(r.error){
          container.set('innerHTML','<p class="error">'+
                                     r.error.description+'</p>');
        } else {
          var res = r.query.results.counted;
          container.set('innerHTML','The tag ' + res.tag+
                                    ' is used ' + res.count + ' times '+
                                    ' in the document at ' + res.url);
        }
      });
    }
});
</script>
</body>
</html>
