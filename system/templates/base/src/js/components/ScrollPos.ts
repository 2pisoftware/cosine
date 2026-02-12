export class ScrollPosComponent {
    static SCROLL_POS_KEY = "COSINE_SCROLL_POSITION";

    public static bindInteractions = () => {
        const savedScrolls: { y: number, url: string, date: number }[] | null
            = tryParseJson(
                window.localStorage.getItem(ScrollPosComponent.SCROLL_POS_KEY)
            ) ?? [];

        const scroll = savedScrolls.find(
            x => x.url === window.location.href
            && x.date + 5 * 60 * 1000 > Date.now()
        );

        if (savedScrolls) {
            if (scroll) {
                window.scrollTo({ top: scroll.y });
            }
        }

        window.addEventListener("beforeunload", () => {
            const toSave = savedScrolls.filter(x => x.url !== window.location.href);
            
            toSave.push({
                y: window.scrollY,
                url: window.location.href,
                date: Date.now()
            });

            if (toSave.length > 5) toSave.shift();

            window.localStorage.setItem(
                ScrollPosComponent.SCROLL_POS_KEY,
                JSON.stringify(toSave)
            );
        })
    }
}

const tryParseJson = (str: string) => {
    try {
        return JSON.parse(str);
    }
    catch (_) {
        return null;
    }
}