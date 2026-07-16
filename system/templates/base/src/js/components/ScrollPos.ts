export class ScrollPosComponent {
    static SCROLL_POS_KEY = "COSINE_SCROLL_POSITION";

    public static bindInteractions = () => {
        const scroll = getSavedPosition(window.location.href);

        if (scroll) {
            window.scrollTo({ top: scroll.y })

            // unsave this position, so that any future `DOM EVENT`
            // triggers in this page do not cause a rescroll
            savePosition(window.location.href);
        }

        window.addEventListener("beforeunload", () => {
            savePosition(window.location.href, window.scrollY)
        })
    }
}

const getSavedPositions = (): { y: number, url: string, date: number }[] => {
    return tryParseJson(
        window.localStorage.getItem(ScrollPosComponent.SCROLL_POS_KEY)
    ) ?? [];
}

const getSavedPosition = (url: string) => {
    return getSavedPositions().find(
        x => x.url === url
            && x.date + 5 * 60 * 1000 > Date.now()
    )
}

// save a new scroll position. if pos is not specified, delete it
const savePosition = (url: string, pos?: number) => {
    const toSave = getSavedPositions().filter(x => x.url !== url);

    if (pos) {
        toSave.push({
            y: pos,
            url: url,
            date: Date.now()
        });
    }

    if (toSave.length > 5) toSave.shift();

    window.localStorage.setItem(
        ScrollPosComponent.SCROLL_POS_KEY,
        JSON.stringify(toSave)
    );
}

const tryParseJson = (str: string | null) => {
    if (!str) return null;
    try {
        return JSON.parse(str);
    }
    catch (_) {
        return null;
    }
}