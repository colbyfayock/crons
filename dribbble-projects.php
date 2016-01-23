<?

/*
 * Grabs dribbble user's project list and writes
 * a json dump for each one found
 */


$dribbbleUser = 'user';
$dribbbleAccessToken = 'accesstoken';

$outputLocation = '/path/to/data/';

function requestData($url, $json = false) {

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5000);

    $response = curl_exec($curl);
    $resultStatus = curl_getinfo($curl);

    if ( $json ) $response = json_decode($response);

    return $resultStatus['http_code'] == 200 ? $response : false;

};

function getDribbbleUserProjectsUrl( $user, $accessToken ) {
    return 'https://api.dribbble.com/v1/users/' . $user . '/projects/?access_token=' . $accessToken;
};

function getDribbbleProjectDetailsUrl( $projectId, $accessToken ) {
    return 'https://api.dribbble.com/v1/projects/' . $projectId . '/shots/?access_token=' . $accessToken;
}

$dribbbleProjects = requestData( getDribbbleUserProjectsUrl($dribbbleUser, $dribbbleAccessToken), true );

foreach( $dribbbleProjects as $project ) {

    $projectData = requestData( getDribbbleProjectDetailsUrl($project->id, $dribbbleAccessToken) );

    try {
        file_put_contents ( $outputLocation . 'project_' . $project->id . '.json' , $projectData );
    } catch(Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

}