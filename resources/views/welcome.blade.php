Hello

<form action="{{route('crawl')}}" method="POST">
    @csrf
    <input type="text" name="url">
    <button type="submit">Crawl</button>
</form>