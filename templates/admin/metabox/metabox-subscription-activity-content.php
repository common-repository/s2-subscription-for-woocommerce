<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
    exit;
}

/**
 * Metabox for Subscription Activity Content
 *
 * @package S2 Subscription\Templates
 * @version 1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */

if ( ! empty( $activities ) ) : ?>
    <ul class="order_notes">
		<?php foreach ( $activities as $activity ) : ?>
            <li class="note">
                <div class="note_content">
                    <p><?php echo wp_kses_post( $activity['description'] ); ?></p>
                </div>
                <p class="meta">
                    <abbr class="exact-date" title="<?php echo esc_attr( $activity['timestamp_date'] ); ?>">
						<?php printf( esc_html( __( 'added on %1$s at %2$s', 's2-subscription' ) ), esc_html( date_i18n( wc_date_format(), strtotime( $activity['timestamp_date'] ) ) ), esc_html( date_i18n( wc_time_format(), strtotime( $activity['timestamp_date'] ) ) ) ); ?>
                    </abbr>
                </p>
            </li>
		<?php endforeach ?>
    </ul>
<?php endif ?>
