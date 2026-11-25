<?php

/*
 * A timed task is sold as a service. Its price grows with the things the client values:
 * starting soon after they asked, and the quality they rated before. Each list is checked
 * from the top; the first row that matches gives the bonus percent.
 */
return [
    // [most minutes from the client's request to the first Start, bonus percent]
    'start_bonus' => [
        [30, 20],
        [120, 10],
        [480, 5],
    ],

    // [lowest average rating this client gave to earlier tasks, bonus percent]
    'quality_bonus' => [
        [4.8, 20],
        [4.5, 10],
        [4.0, 5],
    ],
];
