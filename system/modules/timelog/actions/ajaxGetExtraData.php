<?php

function ajaxGetExtraData_GET(Web $w)
{
    $p = $w->pathMatch("class", "id");

    if (empty($p['class']) || empty($p['id'])) {
        return;
    }

    $object = TimelogService::getInstance($w)->getObject($p['class'], $p['id']);

    if (empty($object->id)) {
        return;
    }

    $form_data = $w->callHook("timelog", "type_options_for_".$p['class'], $object);

    if (!empty($form_data[0])) {
        if (!empty($form_data[0][0]) && is_array($form_data[0][0])) {
            // Add title field
            $title = "<label class='col-12'>Time Type";

            // IS this required?
            $required = null;
            if (!empty(Timelog::$_validation["time_type"])) {
                if (in_array("required", Timelog::$_validation["time_type"])) {
                    $required = "required";
                    $title .= ' <small>Required</small>';
                }
            }

            echo $title;

            // We dont want the structure for multiColForm, we want it for a select
            $select = new \Html\Form\Select([
                "name" => $form_data[0][0][2],
                "options" => $form_data[0][0][4]
            ]);
            if (!is_null($required)) {
                $select->setRequired($required);
            }
            echo $select->__toString()."</label>";
        } elseif (is_a($form_data[0][0], "\Html\Form\Select")) {
            $title = "<label class='col-12'>Time Type";

            // IS this required?
            $required = null;
            if (!empty(Timelog::$_validation["time_type"])) {
                if (in_array("required", Timelog::$_validation["time_type"])) {
                    $required = "required";
                    $title .= ' <small>Required</small>';
                }
            }
            echo $title.$form_data[0][0]->__toString()."</label>";
        }
    }
    return;
}
