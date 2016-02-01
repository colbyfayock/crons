<?

/*
 * Grabs dribbble user's project list and writes
 * a json dump for each one found
 */

if ( file_exists( dirname( __FILE__ ) . '/amazon-goldbox-rss-config.php' ) ) {

    include( dirname( __FILE__ ) . '/amazon-goldbox-rss-config.php' );

} else {

    $outputLocation = '/path/to/dir';
    $affiliateTagId = 'newtag';

}

function requestData($url, $json = false) {

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5000);

    $response = curl_exec($curl);
    $resultStatus = curl_getinfo($curl);

    return $resultStatus['http_code'] == 200 ? $response : false;

};

function getGoldBoxUrl() {
    return 'http://rssfeeds.s3.amazonaws.com/goldbox';
};

function xmlToArray( $feed ) {

    $xml = new SimpleXMLElement($feed);
    $tempFeed = array();

    foreach( $xml->channel->item as $item ) {
        $tempFeed[] = array(
            'title' => (string) $item->title,
            'link' => (string) $item->link,
            'description' => (string) $item->description,
            'pubDate' => (string) $item->pubDate,
            'guid' => (string) $item->guid,
        );
    }

    return $tempFeed;
}

function sortFeedArray( $feed, $key = false ) {

    if ( !$key ) return false;;
    if ( !is_array($feed) ) return false;

    function build_sorter( $key) {
        return function ($a, $b) use ($key) {
            return strnatcmp($b[$key], $a[$key]);
        };
    }

    usort( $feed, build_sorter( $key ) );

    return $feed;

}

function personalizeAffiliateLinks( $items, $tag = false ) {

    if ( !$tag ) return false;

    foreach( $items as &$item ) {

        $item['link'] = str_replace('rssfeeds-20', $tag, $item['link']);
        $item['description'] = str_replace('rssfeeds-20', $tag, $item['description']);
    }

    return $items;

}

$feedData = xmlToArray( requestData( getGoldBoxUrl() ) );

if ( $feedSorted = sortFeedArray( $feedData, 'pubDate' ) ) {
    if ( $feedPersonalized = personalizeAffiliateLinks( array_slice( $feedSorted, 0, 100 ), $affiliateTagId ) ) {
        print_r($feedPersonalized);
    }
}