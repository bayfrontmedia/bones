<?php
/*
 * Partial: Head
 *
 * Sections:
 *
 *   - head (optional)
 *
 * Predefined sections:
 *
 *   - None
 *
 * $data array keys:
 *
 *   - page.title
 *   - page.description
 *
 */
?>
<head>
    <meta charset="UTF-8">
    <title>{{page.title}}</title>
    <meta name="description" content="{{page.description}}">

    ?@place:head

</head>