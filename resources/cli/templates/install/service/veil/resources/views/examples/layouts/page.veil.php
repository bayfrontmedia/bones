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

<body style="font-size:18px;background-color:#f0f0f0;color:#374151;padding:1rem;">

<div id="content-wrap">

    <div id="content" style="background-color:#fff;padding:1rem;">

        @place:content

    </div> <!-- /#content -->

</div> <!-- /#content-wrap -->

@use:examples/layouts/partials/footer

?@place:end_body

</body>
</html>