<?php

return [
    // Messages d'erreur
    'error.invalid_recordset' => 'Le jeu d\'enregistrements doit être un tableau',
    'error.invalid_filter_column' => 'La colonne de filtre doit être une chaîne non vide',
    'error.invalid_compare_operator' => 'Opérateur de comparaison invalide',
    'error.invalid_match_mode' => 'Mode de correspondance invalide',
    'error.invalid_sort_parameter' => 'Le paramètre de tri doit être SORT_ASC, SORT_DESC ou une fonction',
    'error.invalid_color_format' => 'La couleur {color} doit être au format hexadécimal #RRGGBB',
    'error.invalid_calculated_column' => 'La fonction de colonne calculée {function} n\'est pas appelable.',
    'error.invalid_color_by' => 'Paramètre colorBy invalide',
    'error.invalid_getcolor' => 'getColorOf n\'est pas programmé pour gérer COLOR_BY={color}',
    'error.invalid_precision' => 'La précision décimale doit être un entier non négatif',
    'error.color_not_implemented' => 'Fonction de couleur non implémentée pour le mode : {mode}',
    'error.filter_not_callable' => 'La fonction de filtre doit être appelable',
    'error.column_function_mismatch' => 'Le nombre de noms de colonnes et de fonctions ne correspond pas',
    'error.invalid_value_function' => 'Fonction de valeur non reconnue : {function}',
    'error.value_function_count_mismatch' => 'Le nombre de champs de valeur et de fonctions ne correspond pas.',
    'error.invalid_display_mode' => 'Impossible de formater les données comme : {mode}',
    'error.invalid_data_structure' => 'Impossible de trouver ["_val"] dans la ligne de données (structure invalide)',
    'error.invalid_filter' => 'Filtre invalide, doit implémenter FilterInterface',
];