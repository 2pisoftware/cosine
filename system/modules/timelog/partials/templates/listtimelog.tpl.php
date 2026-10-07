<?php

/**
 * @var Web $w
 * @var string $class
 * @var string $id
 * @var int $total
 * @var string $pagination
 * @var string $table
 */
?>

<div class="d-flex align-items-center justify-content-between">
    <?php echo HtmlBootstrap5::box(
        href: "/timelog/edit?class={$class}&id={$id}" . (!empty($redirect) ? "&redirect=$redirect" : ''),
        title: "Add new timelog",
        button: true,
        class: "bg-primary"
    ); ?>
    <h4> <?php echo TaskService::getInstance($w)->getFormatPeriod($total); ?> </h4>
</div>

<?php

echo $pagination;
echo $table;
