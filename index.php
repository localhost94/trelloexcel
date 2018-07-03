<?php

$myfile = fopen("board.json", "r") or die("Unable to open file!");
$json = fread($myfile,filesize("board.json"));
fclose($myfile);

$object = json_decode($json);

echo '<pre>';

$members = [];
foreach ($object->members as $key => $value) {
    $members[$value->id] = $value->fullName;
}

$lists = [];
foreach ($object->lists as $key => $value) {
    $lists[$value->id] = $value->name;
}

$labels = [];
foreach ($object->labels as $key => $value) {
    $labels[$value->id] = $value->name;
}

$actions = [];
$actionCard = [];
$reviewCard = [];
$doneCard = [];
foreach ($object->actions as $key => $value) {
    $actions[$value->id] = $value->type;
    if (isset($value->data->card)) {
        $actionCard[$value->data->card->id][$value->id] = $value;

        if (isset($value->data->listAfter)) {
            if ($value->data->listAfter->name == 'Review') {
                $reviewCard[$value->data->card->id][$value->id] = $value;
            }
            if ($value->data->listAfter->name == 'Done') {
                $doneCard[$value->data->card->id][$value->id] = $value;
            }
        }
    }
}

$index = 0;
$cards = [];
$data = [];

$data[0] = [
    'no' => 'No',
    'task' => 'Task',
    'assign' => 'Assign',
    'status' => 'Status',
    'label' => 'Label',
    'request_by' => 'Request By',
    'created_date' => 'Created Date',
    'due_date' => 'Due Date',
    'done_date' => 'Done Date',
    'diff' => 'Diff',
    'trello' => 'Trello',
    'notes' => 'Notes',
];
foreach ($object->cards as $key => $value) {
    $index++;

    $member = [];
    if (is_array($value->idMembers)) {
        foreach ($value->idMembers as $k => $v) {
            $member[] = $members[$v];
        }
    }

    $label = [];
    if (is_array($value->idLabels)) {
        foreach ($value->idLabels as $k => $v) {
            $label[] = $labels[$v];
        }
    }

    $dueTime = strtotime($value->due);
    $due = date('Y-m-d', $dueTime);

    $firstAction = [];
    $requestBy = '';
    $createdDate = '';
    $reviewDate = '';
    $doneDate = '';
    $diffReview = '';
    $diffDone = '';
    $diff = '';
    if (isset($actionCard[$value->id])) {
        $firstAction = end($actionCard[$value->id]);
        $requestBy = $firstAction->memberCreator->fullName;
        
        $createdDateTime = strtotime($firstAction->date);
        $createdDate = date('Y-m-d', $createdDateTime);
    }

    if (isset($reviewCard[$value->id])) {
        $latestActionReview = reset($reviewCard[$value->id]);
        
        $reviewDateTime = strtotime($latestActionReview->date);
        $reviewDate = date('Y-m-d', $reviewDateTime);

        $date1 = new DateTime($due);
        $date2 = new DateTime($reviewDate);
        $interval = $date1->diff($date2);
        $diffReview = $interval->days;
    }

    if (isset($doneCard[$value->id])) {
        $latestActionDone = reset($doneCard[$value->id]);
        
        $doneDateTime = strtotime($latestActionDone->date);
        $doneDate = date('Y-m-d', $doneDateTime);

        $date1 = new DateTime($due);
        $date2 = new DateTime($doneDate);
        $interval = $date1->diff($date2);
        $diffDone = $interval->days;
    }
    if ($due != '1970-01-01') {
        $diff = $diffReview ? $diffReview : $diffDone;
    }

    // $cards[$value->id] = $value->name;
    $data[$index] = [
        'no' => $index,
        'task' => $value->name,
        'assign' => implode(', ', $member),
        'status' => $lists[$value->idList],
        'label' => implode(', ', $label),
        'request_by' => $requestBy,
        'created_date' => $createdDate,
        'due_date' => $due != '1970-01-01' ? $due : '',
        'done_date' => $reviewDate ? $reviewDate : $doneDate,
        'diff' => $diff,
        'trello' => $value->shortUrl,
        'notes' => '',
    ];
}

$fp = fopen('board.csv', 'w');

foreach ($data as $fields) {
    fputcsv($fp, $fields);
}

fclose($fp);

print_r($data);
// print_r($members);
// print_r($lists);
// print_r($cards);
// print_r($actions);
