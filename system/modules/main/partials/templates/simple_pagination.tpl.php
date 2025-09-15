<?php

function makeUrl(int $page, string $base_url, string $param)
{
	$parsed = parse_url($base_url);

	return $parsed["path"] . "?" . $parsed["query"] . "&" . http_build_query([
		$param => $page
	]);
}

$last = ceil($total / $page_size);
?>

<nav aria-label="pagination">
	<ul class="pagination justify-content-center flex-wrap">
		<?php if ($current > 1) : ?>
			<li class="page-item">
				<a
					class="page-link"
					href="<?php echo makeUrl(1, $base_url, $param) ?>">
					1
				</a>
			</li>
		<?php endif; ?>

		<?php if ($current > 2) : ?>
			<li class="page-item">
				<a
					class="page-link"
					href="<?php echo makeUrl($current - 1, $base_url, $param) ?>">
					<?php echo $current - 1 ?>
				</a>
			</li>
		<?php endif; ?>

		<li class="page-item disabled">
			<a
				class="page-link"
				href="<?php echo makeUrl($current, $base_url, $param) ?>">
				<?php echo $current; ?>
			</a>
		</li>

		<?php if ($current < $last - 1) : ?>
			<li class="page-item">
				<a
					class="page-link"
					href="<?php echo makeUrl($current + 1, $base_url, $param) ?>">
					<?php echo $current + 1; ?>
				</a>
			</li>
		<?php endif; ?>

		<?php if ($current < $last) : ?>
			<li class="page-item">
				<a
					class="page-link"
					href="<?php echo makeUrl($last, $base_url, $param) ?>">
					<?php echo $last; ?>
				</a>
			</li>
		<?php endif; ?>
	</ul>
</nav>