<script type="text/javascript">
    $(function() {
        setSearchBoxTarget('Recipes');
        
        $('#loadToday').click(function() {
            ajaxGet('MealPlans/index/<?php echo date('m-d-Y');?>');
        });
        
        $(document).off("saved.meal");
        $(document).on("saved.meal", function() {
            $('#editMealDialog').dialog('close');
            ajaxGet('MealPlans/index/<?php echo $date;?>');
        });
    });
</script>
<h2><?php echo __('Meal Plan - Weekly'); ?></h2>
<div class="actions">
    <ul>
        <li><?php echo $this->Html->link(__('Add to Shopping List'), array('action' => 'addToShoppingList', $date), array('class' => 'ajaxLink'));?></li>
    </ul>
</div>
<div class="mealPlans index">
        <?php echo $this->Html->link('<', 
                array('action' => 'index', $previousWeek[1].'-'.$previousWeek[0].'-'.$previousWeek[2]), 
                array('class' => 'ajaxNavigation calendarNavigation')); ?>
                    
        <?php echo $startDate;?> - <?php echo $endDate;?>
        
        <?php echo $this->Html->link('>', 
                array('action' => 'index', $nextWeek[1].'-'.$nextWeek[0].'-'.$nextWeek[2]), 
                array('class' => 'ajaxNavigation calendarNavigation')); ?>
        <button id="loadToday"><?php echo __('Today');?></button>
        
        <br/><br/>
        <div id="weeklyContainer">
        <?php 
        $day = $startDayOfWeek;
        for ($i=0; $i<7; $i++) : ?>
                    <div class="dayHeader">
            <?php echo $weekDays[$day];?>
        </div>
            <?php
		// if we get to 6 wrap around to the next day (sunday)
		if ($day == 6) $day = 0;
		else $day++;
	endfor;?>

        <?php for ($i=0; $i < 7; $i++) : ?>
        <div class="dayContent 
            <?php echo ($i == 6) ? "endOfRow" : "";?> 
            <?php echo ($weekList[$i][1] != $currentMonth) ? "nextMonth" : "";?>
            <?php echo ($weekList[$i][1] == $realMonth && $weekList[$i][0] == $realDay && $weekList[$i][2] == $realYear ) ? "currentDay" : "";?>
        ">
            <?php 
            $dateIndex = $weekList[$i][2] . "-" . str_pad($weekList[$i][1], 2, "0",STR_PAD_LEFT) . "-" . str_pad($weekList[$i][0], 2, "0",STR_PAD_LEFT);
            echo $this->Html->link($weekList[$i][0], array('action' => 'edit', "undefined", $dateIndex), array('class' => 'ajaxLink', 'targetId' => 'editMealDialog'));?>
            <br/>
                <?php 
                if (isset($mealList[$dateIndex])) {
                    foreach ($mealList[$dateIndex] as $meal) {
                        $mealPlanId = $meal->id;
                        $mealName = $meal->recipe->name;
                        echo "<div class='mealType mealType" . $meal->meal_name->id . "'>"; 
                        echo $this->Form->postLink("", array('action' => 'delete', $mealPlanId), array('class' => 'ui-icon ui-icon-circle-close'), __('Are you sure you want to delete meal "%s"?', $mealName));
                        echo $this->Html->link($mealName, array('action' => 'edit', $mealPlanId, $dateIndex), 
                                array('class' => 'ajaxLink', 'targetId' => 'editMealDialog'));
                        
                        echo "</div>";
                        
                    }
            } ?>
        </div>
        <?php endfor; ?>
        
        <div class="clear"></div>
</div>
<br/>
<br/>
<div class="mealLegend">
    <div><strong>Legend</strong></div>
    <div class="mealType mealType1"><?php echo __('Breakfast');?></div>
    <div class="mealType mealType3"><?php echo __('Lunch');?></div>
    <div class="mealType mealType5"><?php echo __('Dinner');?></div>
    <div class="mealType mealType6"><?php echo __('Dessert');?></div>
</div>

