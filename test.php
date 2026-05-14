<?php
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo json_encode(['success'=>1,'message'=>'POST works']);
} else {
    echo json_encode(['success'=>0,'message'=>'Send POST']);
}
