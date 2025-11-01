<?php
/*
 * Example quiz question bank for demonstration.  In a production system
 * quizzes could be stored in the database or loaded from a SCORM package.
 * Each quiz is keyed by its ID and contains an array of questions.  Each
 * question consists of a text prompt, an array of options and the index of
 * the correct option (0‑based).
 */

$QUIZZES = [
    1 => [
        ['q' => 'What is 2 + 2?', 'options' => ['3', '4', '5'], 'answer' => 1],
        ['q' => 'Solve for x: x − 3 = 2', 'options' => ['1', '5', '−1'], 'answer' => 1],
        ['q' => 'Which of the following is a variable?', 'options' => ['5', 'x', '10'], 'answer' => 1]
    ],
    2 => [
        ['q' => 'The sum of interior angles of a triangle is?', 'options' => ['90°', '180°', '360°'], 'answer' => 1],
        ['q' => 'A right angle is equal to?', 'options' => ['45°', '90°', '180°'], 'answer' => 1],
        ['q' => 'How many sides does a hexagon have?', 'options' => ['5', '6', '8'], 'answer' => 1]
    ],
    3 => [
        ['q' => 'What force pulls objects toward the Earth?', 'options' => ['Magnetism', 'Gravity', 'Friction'], 'answer' => 1],
        ['q' => 'What is the state of matter of water at room temperature?', 'options' => ['Solid', 'Liquid', 'Gas'], 'answer' => 1],
        ['q' => 'Which of the following is a renewable energy source?', 'options' => ['Coal', 'Wind', 'Oil'], 'answer' => 1]
    ]
];