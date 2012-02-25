<?php if(!empty($schedules)) { 
	$i = true;
?>
	<ul class="schedule-list">
	<?php foreach($schedules as $schedule) { 
		$i = !$i;
		$meta = get_post_custom($schedule->ID);
	?> 
		
		<li class="schedule-item<?php echo $i ? ' odd' : ''?>">
			<div class="schedule-main">
			<?php 
				if(!empty($meta['g3_schedules_image_id'][0])) {
					$img = wp_get_attachment_image_src($meta['g3_schedules_image_id'][0]); 
				} else {
					$img = false;
				}
				
				if($img) { 
					if(!empty($meta['g3_schedules_link'][0])) { ?>
						<a href="<?php echo(esc_attr($meta['g3_schedules_link'][0])); ?>">
							<img class="schedule-image" src="<?php echo(esc_attr($img[0])); ?>" alt="" />
						</a>
					<?php } else { ?>
						<img class="schedule-image" src="<?php echo(esc_attr($img[0])); ?>" alt=""/>
					<?php } ?>
				<?php } else { ?>
				<img class="schedule-image" src="/banners/nophoto.png" alt=""/>
				<?php } ?>
				<div class="schedule-info">
					<h6><?php echo(esc_attr($schedule->post_title)); ?></h6>
					<?php echo(esc_attr($meta['g3_schedules_day'][0])); ?>: 
					<?php echo(esc_attr(sprintf('%02d:00', $meta['g3_schedules_start'][0]))); ?> -
					<?php echo(esc_attr(sprintf('%02d:00', $meta['g3_schedules_end'][0]))); ?>
				</div>
			</div>
			<div style="clear:both;"></div>
		</li>
	<?php } ?>
	</ul>
<?php } ?>
