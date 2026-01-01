<?php
/**
 * Training Template Library
 * Goal: consistency (sustainable adherence)
 * Templates organized by days_per_week × session_duration
 */

return [
  'consistency' => [
    // 3 days per week
    '3d_30m' => [
      'days_per_week' => 3,
      'session_duration' => 30,
      'split_type' => 'full_body',
      'day_types' => [
        'A' => [
          ['pattern' => 'squat', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'push', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'pull', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'core', 'priority' => 2, 'sets' => 3, 'reps' => 30],
        ],
        'B' => [
          ['pattern' => 'hinge', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'push', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'pull', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'core', 'priority' => 2, 'sets' => 3, 'reps' => 30],
        ],
      ],
      'weekly_pattern' => ['A', 'B', 'A'], // Mon/Wed/Fri
    ],

    '3d_45m' => [
      'days_per_week' => 3,
      'session_duration' => 45,
      'split_type' => 'full_body',
      'day_types' => [
        'A' => [
          ['pattern' => 'squat', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'push', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'pull', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'core', 'priority' => 2, 'sets' => 3, 'reps' => 30],
          ['pattern' => 'cardio', 'priority' => 3, 'sets' => 1, 'reps' => 12],
        ],
        'B' => [
          ['pattern' => 'hinge', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'push', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'pull', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'core', 'priority' => 2, 'sets' => 3, 'reps' => 30],
          ['pattern' => 'cardio', 'priority' => 3, 'sets' => 1, 'reps' => 12],
        ],
      ],
      'weekly_pattern' => ['A', 'B', 'A'],
    ],

    '3d_60m' => [
      'days_per_week' => 3,
      'session_duration' => 60,
      'split_type' => 'full_body',
      'day_types' => [
        'A' => [
          ['pattern' => 'squat', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'push', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'pull', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'core', 'priority' => 2, 'sets' => 3, 'reps' => 30],
          ['pattern' => 'push', 'priority' => 3, 'sets' => 2, 'reps' => 12], // accessory
          ['pattern' => 'cardio', 'priority' => 3, 'sets' => 1, 'reps' => 12],
        ],
        'B' => [
          ['pattern' => 'hinge', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'push', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'pull', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'core', 'priority' => 2, 'sets' => 3, 'reps' => 30],
          ['pattern' => 'pull', 'priority' => 3, 'sets' => 2, 'reps' => 12], // accessory
          ['pattern' => 'cardio', 'priority' => 3, 'sets' => 1, 'reps' => 12],
        ],
      ],
      'weekly_pattern' => ['A', 'B', 'A'],
    ],

    // 4 days per week
    '4d_30m' => [
      'days_per_week' => 4,
      'session_duration' => 30,
      'split_type' => 'full_body',
      'day_types' => [
        'A' => [
          ['pattern' => 'squat', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'push', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'pull', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'core', 'priority' => 2, 'sets' => 3, 'reps' => 30],
        ],
        'B' => [
          ['pattern' => 'hinge', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'push', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'pull', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'core', 'priority' => 2, 'sets' => 3, 'reps' => 30],
        ],
      ],
      'weekly_pattern' => ['A', 'B', 'A', 'B'], // Mon/Tue/Thu/Fri
    ],

    '4d_45m' => [
      'days_per_week' => 4,
      'session_duration' => 45,
      'split_type' => 'full_body',
      'day_types' => [
        'A' => [
          ['pattern' => 'squat', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'push', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'pull', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'core', 'priority' => 2, 'sets' => 3, 'reps' => 30],
          ['pattern' => 'cardio', 'priority' => 3, 'sets' => 1, 'reps' => 12],
        ],
        'B' => [
          ['pattern' => 'hinge', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'push', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'pull', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'core', 'priority' => 2, 'sets' => 3, 'reps' => 30],
          ['pattern' => 'cardio', 'priority' => 3, 'sets' => 1, 'reps' => 12],
        ],
      ],
      'weekly_pattern' => ['A', 'B', 'A', 'B'],
    ],

    '4d_60m' => [
      'days_per_week' => 4,
      'session_duration' => 60,
      'split_type' => 'full_body',
      'day_types' => [
        'A' => [
          ['pattern' => 'squat', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'push', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'pull', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'core', 'priority' => 2, 'sets' => 3, 'reps' => 30],
          ['pattern' => 'push', 'priority' => 3, 'sets' => 2, 'reps' => 12],
          ['pattern' => 'cardio', 'priority' => 3, 'sets' => 1, 'reps' => 12],
        ],
        'B' => [
          ['pattern' => 'hinge', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'push', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'pull', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'core', 'priority' => 2, 'sets' => 3, 'reps' => 30],
          ['pattern' => 'pull', 'priority' => 3, 'sets' => 2, 'reps' => 12],
          ['pattern' => 'cardio', 'priority' => 3, 'sets' => 1, 'reps' => 12],
        ],
      ],
      'weekly_pattern' => ['A', 'B', 'A', 'B'],
    ],

    // 5 days per week
    '5d_30m' => [
      'days_per_week' => 5,
      'session_duration' => 30,
      'split_type' => 'full_body',
      'day_types' => [
        'A' => [
          ['pattern' => 'squat', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'push', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'pull', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'core', 'priority' => 2, 'sets' => 3, 'reps' => 30],
        ],
        'B' => [
          ['pattern' => 'hinge', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'push', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'pull', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'core', 'priority' => 2, 'sets' => 3, 'reps' => 30],
        ],
      ],
      'weekly_pattern' => ['A', 'B', 'A', 'B', 'A'], // Mon/Tue/Thu/Fri/Sat
    ],

    '5d_45m' => [
      'days_per_week' => 5,
      'session_duration' => 45,
      'split_type' => 'full_body',
      'day_types' => [
        'A' => [
          ['pattern' => 'squat', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'push', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'pull', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'core', 'priority' => 2, 'sets' => 3, 'reps' => 30],
          ['pattern' => 'cardio', 'priority' => 3, 'sets' => 1, 'reps' => 12],
        ],
        'B' => [
          ['pattern' => 'hinge', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'push', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'pull', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'core', 'priority' => 2, 'sets' => 3, 'reps' => 30],
          ['pattern' => 'cardio', 'priority' => 3, 'sets' => 1, 'reps' => 12],
        ],
      ],
      'weekly_pattern' => ['A', 'B', 'A', 'B', 'A'],
    ],

    '5d_60m' => [
      'days_per_week' => 5,
      'session_duration' => 60,
      'split_type' => 'full_body',
      'day_types' => [
        'A' => [
          ['pattern' => 'squat', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'push', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'pull', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'core', 'priority' => 2, 'sets' => 3, 'reps' => 30],
          ['pattern' => 'push', 'priority' => 3, 'sets' => 2, 'reps' => 12],
          ['pattern' => 'cardio', 'priority' => 3, 'sets' => 1, 'reps' => 12],
        ],
        'B' => [
          ['pattern' => 'hinge', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'push', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'pull', 'priority' => 1, 'sets' => 3, 'reps' => 10],
          ['pattern' => 'core', 'priority' => 2, 'sets' => 3, 'reps' => 30],
          ['pattern' => 'pull', 'priority' => 3, 'sets' => 2, 'reps' => 12],
          ['pattern' => 'cardio', 'priority' => 3, 'sets' => 1, 'reps' => 12],
        ],
      ],
      'weekly_pattern' => ['A', 'B', 'A', 'B', 'A'],
    ],
  ],
];

