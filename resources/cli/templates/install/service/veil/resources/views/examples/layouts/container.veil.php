<?php
/*
 * Layout: Page
 *
 * Sections:
 *
 *   - content (required)
 *
 *   - head (optional)
 *   - end_body (optional)
 *
 * Predefined sections:
 *
 *   - examples/layouts/partials/head
 *   - examples/layouts/partials/footer
 *
 * $data array keys:
 *
 *   - page.title
 *   - page.description
 *   - year
 *
 */
?>
<!DOCTYPE html>
<html lang="en">

@use:examples/layouts/partials/head

<body>

<img class="logo" src="https://cdn1.onbayfront.com/bfm/brand/bfm-logo.svg"
     alt="Bayfront Media"/>

<div class="container">

    <main class="content">

        @place:content

    </main> <!-- /.content -->

</div> <!-- /.container -->

@use:examples/layouts/partials/footer

?@place:end_body

</body>
</html>