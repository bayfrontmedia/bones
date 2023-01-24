@section:head

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans&display=swap" rel="stylesheet">

<style>
    html {
        font-family: 'Open Sans', sans-serif;
    }
</style>
@endsection

@section:content

<div id="content">

    <h1>{{page.title}}</h1>
    <p>This is an example of a page layout.</p>
    <p>It is encouraged to filter the view content through the <code>response.body</code> filter
        to allow additional flexibility, such as censoring words, automatically adding links,
        or creating custom template tags.</p>
</div>

@endsection

@use:examples/layouts/page