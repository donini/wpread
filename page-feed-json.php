<?php
header('Content-Type: application/json; charset=utf-8' );
$language = get_query_var('lang');
echo Alexa_Reader::get_recent_feed_json( $language );
