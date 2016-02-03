<?

/*
 * Grabs Amazon's Goldbox RSS Feed
 * Outputs to a valid RSS document
 */

date_default_timezone_set( 'UTC' );

if ( file_exists( dirname( __FILE__ ) . '/amazon-goldbox-rss-config.php' ) ) {

    include( dirname( __FILE__ ) . '/amazon-goldbox-rss-config.php' );

} else {

    $outputLocation = '/path/to/dir';
    $outputFilename = 'amazon-goldbox-100.xml';
    $affiliateTagId = 'newtag';
    $feedLimit = 100;

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
            'pubDate' => (string) strtotime( $item->pubDate ),
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

function makeRssItem( $item ) {

    $rssItem = '';

    $rssItem .= '<item>';
    
    $rssItem .= '<title>' . htmlspecialchars( $item['title'] ) . '</title>';
    $rssItem .= '<link>' . htmlspecialchars( $item['link'] ) . '</link>';
    $rssItem .= '<description>' . htmlspecialchars( $item['description'] ) . '</description>';
    $rssItem .= '<pubDate>' . htmlspecialchars( date(DATE_RSS, $item['pubDate']) ) . '</pubDate>';
    $rssItem .= '<guid>' . htmlspecialchars( $item['guid'] ) . '</guid>';

    $rssItem .= '</item>';


    return $rssItem;

}

function makeRssDoc( $items ) {

    $dom = new DOMDocument;
    $dom->preserveWhiteSpace = FALSE;
    $dom->formatOutput = TRUE;

    $rss = '';

    $rss .= '<rss version="2.0">';
    $rss .= '<channel>';

    $rss .= '<title>Amazon.com Gold Box Deals</title>';
    $rss .= '<link>http://www.amazon.com/gp/goldbox</link>';
    $rss .= '<description>Amazon.com Gold Box Deals</description>';
    $rss .= '<lastBuildDate>' . date(DATE_RSS) . '</lastBuildDate>';


    foreach ( $items as $item ) {
        $rss .= makeRssItem($item);
    }

    $rss .= '</channel>';
    $rss .= '</rss>';

    $dom->loadXML($rss);

    return $dom->saveXml();;

}

$feedData = xmlToArray( requestData( getGoldBoxUrl() ) );

if ( $feedSorted = sortFeedArray( $feedData, 'pubDate' ) ) {
    if ( $feedPersonalized = personalizeAffiliateLinks( array_slice( $feedSorted, 0, $feedLimit ), $affiliateTagId ) ) {

        try {
            file_put_contents ( $outputLocation . '/' . $outputFilename , makeRssDoc($feedPersonalized) );
        } catch(Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }

    }
}