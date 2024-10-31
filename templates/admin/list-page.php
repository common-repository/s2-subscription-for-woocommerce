<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Subscription List Page
 *
 * @package S2 Subscription\Templates
 * @version 1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
?>

<div class="wrap">
	<div id="poststuff">
		<div id="post-body" class="metabox-holder">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<form method="get">
						<input type="hidden" name="page" value="s2-subscription" />
						<input type="hidden" name="tab" value="subscriptions" />
						<?php $subscription_list->search_box( 'search', 'search_id' ); ?>
					</form>
					<form method="get">
						<input type="hidden" name="page" value="s2-subscription" />
						<input type="hidden" name="tab" value="subscriptions" />
						<?php
						$subscription_list->prepare_items();
						$subscription_list->display();
						?>
					</form>
				</div>
			</div>
		</div>
		<br class="clear" />
	</div>
</div>
