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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{page.title}}</title>
    <meta name="description" content="{{page.description}}">

    <link rel="icon" href="https://cdn1.onbayfront.com/bfm/brand/favicons/favicon-32x32.png">

    ?@place:head

    <style>
        html {
            font-family: 'Open Sans', sans-serif;
        }

        body {
            background-color: #f2f2f2;
            color: #404040;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 18px;
            font-weight: 400;
            line-height: 1.6;
            padding: 1rem;
            text-align: center;
            -moz-osx-font-smoothing: grayscale;
        }

        a {
            color: #404040;
            text-decoration: underline;
        }

        a:hover {
            text-decoration: none;
        }

        .logo {
            max-width: 400px;
            width: 70%;
            height: auto;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 2rem;
        }

        footer {
            color: #808080;
            font-size: .9rem;
        }

        footer a {
            color: #808080;
            text-decoration: underline;
        }

        footer a:hover {
            text-decoration: none;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .content {
            background-color: #fff;
            border: 1px solid #d9d9d9;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            padding: 1rem;
            width: 600px;
        }

    </style>

</head>