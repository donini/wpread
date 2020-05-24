<?php
$language = get_query_var('lang');
$feeds    = Alexa_Reader::get_recent_feed( $language, -1 );

if ( $feeds->have_posts() ) {
    while ( $feeds->have_posts() ) {
        $feeds->the_post();
        
        $item_channel    = wp_get_post_terms( get_the_ID(), 'channel', [ 'fields' => 'names' ] );
        if ( is_array( $item_channel ) ) {
            $item_channel = 'do ' . $item_channel[0];
          } else {
            $item_channel = '';
          }
?>
    <article>
        <h1><a href="<?php echo get_the_permalink(); ?>" title=""><?php echo get_the_title(); ?></a></h1>
        <p><?php echo get_the_content(); ?></p>
        <span>Em <?php echo get_the_date(); ?> por <?php echo get_the_author(); ?> <?php echo $item_channel; ?></span>
    </article>
<?php
    }
}
?>
