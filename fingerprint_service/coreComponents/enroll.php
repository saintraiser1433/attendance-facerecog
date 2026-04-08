<?php
/**
 * this file uses the enrollment class to
 * enroll users
 */

require_once("../coreComponents/basicRequirements.php");

if (!empty($_POST["data"])) {
    $user_data = json_decode($_POST["data"]);

    $index_finger_string_array = $user_data->index_finger;
    $middle_finger_string_array = $user_data->middle_finger;

    $enrolled_index_finger = enroll_fingerprint($index_finger_string_array);
    $enrolled_middle_finger = enroll_fingerprint($middle_finger_string_array);

    $index_ok = is_string($enrolled_index_finger) && trim($enrolled_index_finger) !== '' && $enrolled_index_finger !== "enrollment failed";
    $middle_ok = is_string($enrolled_middle_finger) && trim($enrolled_middle_finger) !== '' && $enrolled_middle_finger !== "enrollment failed";

    if ($index_ok && $middle_ok){
        # todo: return the enrolled fmds instead
        $output = ["enrolled_index_finger"=>$enrolled_index_finger, "enrolled_middle_finger"=>$enrolled_middle_finger];
        echo json_encode($output);
    }
    else {
        echo json_encode([
            "error" => "enrollment failed",
            "details" => [
                "index_status" => $enrolled_index_finger,
                "middle_status" => $enrolled_middle_finger
            ]
        ]);
    }
} else {
    echo json_encode("error! no data provided in post request");
}

