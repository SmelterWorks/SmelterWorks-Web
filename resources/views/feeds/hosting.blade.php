{!! '<' . '?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0">
    <channel>
        <title>{{ $feed['title'] }}</title>
        <link>{{ $feed['link'] }}</link>
        <description>{{ $feed['description'] }}</description>
        <language>{{ $feed['language'] }}</language>
        <lastBuildDate>{{ $feed['last_build']->toRssString() }}</lastBuildDate>
        @foreach ($feed['items'] as $item)
            <item>
                <title>{{ $item['title'] }}</title>
                <link>{{ $item['link'] }}</link>
                <guid isPermaLink="false">{{ $item['guid'] }}</guid>
                <pubDate>{{ $item['pub_date']->toRssString() }}</pubDate>
                <description>{{ $item['description'] }}</description>
            </item>
        @endforeach
    </channel>
</rss>
