export class ScrollPosComponent {
    public static bindInteractions = () => {
        const SCROLL_POS_KEY = "COSINE_SCROLL_POSITION";
        const savedScrolls: { y: number, url: string }[] | null
            = tryParseJson(window.localStorage.getItem(SCROLL_POS_KEY)) ?? [];
        const scroll = savedScrolls.find(x => x.url === window.location.href);

        if (savedScrolls) {
            if (scroll) {
                window.scrollTo({ top: scroll.y });
            }
        }

        window.addEventListener("beforeunload", () => {
            const toSave = savedScrolls.filter(x => x.url !== window.location.href);
            toSave.push({ y: window.scrollY, url: window.location.href });

            if (toSave.length > 5) toSave.shift();

            window.localStorage.setItem(SCROLL_POS_KEY, JSON.stringify(toSave));
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