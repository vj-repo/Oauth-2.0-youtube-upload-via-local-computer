<?php
error_reporting(E_ALL);

$htmlBody = "";

require_once 'src/Google_Client.php';
require_once 'src/contrib/Google_YouTubeService.php';
session_start();


$OAUTH2_CLIENT_ID = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.apps.googleusercontent.com';
$OAUTH2_CLIENT_SECRET = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

$client = new Google_Client();
$client->setClientId($OAUTH2_CLIENT_ID);
$client->setClientSecret($OAUTH2_CLIENT_SECRET);
$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
    FILTER_SANITIZE_URL);
$client->setRedirectUri($redirect);

$youtube = new Google_YoutubeService($client);

if (isset($_GET['code'])) {
  if (strval($_SESSION['state']) !== strval($_GET['state'])) {
    die('The session state did not match.');
  }

  $client->authenticate();
  $_SESSION['token'] = $client->getAccessToken();
  header('Location: ' . $redirect);
}

if (isset($_SESSION['token'])) {
  $client->setAccessToken($_SESSION['token']);
}

if(isset($_POST['submit']))
{

	$filename=$_FILES['file']['name'];
        $filesize=$_FILES['file']['size'];
        $filetype=$_FILES['file']['type'];

        $tmpfile=$_FILES['file']['tmp_name'];

        $unique=str_shuffle("abcde").$filename;
		$path = "videos/".$unique;
		
		if ((($filetype == "video/avi")
	    || ($filetype == "video/mpeg")
	    || ($filetype == "video/mpg")
	    || ($filetype == "video/mov")
		|| ($filetype == "video/wmv") 
		|| ($filetype == "video/rm") 
		|| ($filetype == "video/mp4"))
	    && ($filesize < 8388608 and $filesize > 20))
		{
    		move_uploaded_file($tmpfile,$path);
	        echo'file is uploaded';

			 try{
				    $videoPath = $path;

				    $snippet = new Google_VideoSnippet();
				    $snippet->setTitle("Test title");
				    $snippet->setDescription("Test description");
				    $snippet->setTags(array("tag1", "tag2"));
					$snippet->setCategoryId("22");
					
					$status = new Google_VideoStatus();
				    $status->privacyStatus = "public";

				    $video = new Google_Video();
				    $video->setSnippet($snippet);
				    $video->setStatus($status);

					$chunkSizeBytes = 1 * 1024 * 1024;
	
					$media = new Google_MediaFileUpload('video/*', null, true, $chunkSizeBytes);
    				$media->setFileSize(filesize($videoPath));

					$insertResponse = $youtube->videos->insert("status,snippet", $video, array('mediaUpload' => $media));
					$uploadStatus = false;

					$handle = fopen($videoPath, "rb");

				    while (!$uploadStatus && !feof($handle)) 
					{
						$chunk = fread($handle, $chunkSizeBytes);
						$uploadStatus = $media->nextChunk($insertResponse, $chunk);
				    }

				    fclose($handle);
					
					$htmlBody .= "<h3>Video Uploaded</h3><ul>";
				    $htmlBody .= sprintf('<li>%s (%s)</li>',
				        $uploadStatus['snippet']['title'],
				        $uploadStatus['id']);
				
				    $htmlBody .= '</ul>';
						

					} catch (Google_ServiceException $e) {
					    $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
					        htmlspecialchars($e->getMessage()));
					} catch (Google_Exception $e) {
					    $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
					        htmlspecialchars($e->getMessage()));
					}
					
					$_SESSION['token'] = $client->getAccessToken();

		}
        else
    	{
        	echo'failure in uploading';
		}
}
if ($client->getAccessToken()) {

} else {
  // If the user hasn't authorized the app, initiate the OAuth flow
  $state = mt_rand();
  $client->setState($state);
  $_SESSION['state'] = $state;

  $authUrl = $client->createAuthUrl();
  $htmlBody = <<<END
  <h3>Authorization Required</h3>
  <p>You need to <a href="$authUrl">authorize access</a> before proceeding.<p>
END;
}
?>

<!doctype html>
<html>
<head>
<title>Video Uploaded</title>
</head>
<body>
  <?php
	if($htmlBody)
		echo $htmlBody;	
?>
<?php
if ($client->getAccessToken()) {?>
	
					<form action="" method="post" enctype="multipart/form-data">
					  <input id="file" type="file" name="file"/>
					  <div id="errMsg" style="display:none;color:red">
					    You need to specify a file.
					  </div>
					  <input type="submit" name="submit" value="Upload" value="go" />
					
					</form>
<?php }
?>
</body>
</html>
