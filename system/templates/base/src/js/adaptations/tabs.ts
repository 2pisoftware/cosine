
export class TabAdaptation {

    static changeTab(hash) {
        if (hash.length <= 0) {
            return
        }

        document.querySelectorAll(".tab-body > div")?.forEach((el: Element) => el.classList.remove('active'));
        document.querySelectorAll(".tab-head > a")?.forEach((el: Element) => el.classList.remove('active'));

        if (hash[0] === "#"){
            hash = hash.substr(1);
        }
        
        if (history.replaceState) {
            history.replaceState(null, null, '#' + hash);
        } else {
            location.hash = '#' + hash;
        }
        
        document.querySelector(".tab-body > div[id='" + hash + "']")?.classList.add('active');
        document.querySelector('.tab-head > a[href$="' + hash + '"]')?.classList.add('active');
        
        // Update codemirror instances
        document.querySelectorAll('.CodeMirror')?.forEach((el: Element) => {
            if (el.hasOwnProperty('CodeMirror')) {
                (el as any).CodeMirror.refresh();
            }
        }); 
    }

    static bindTabInteractions() {
        // Backwards compat tabs
        document.querySelectorAll('.tab-head > a').forEach((el: Element) => {
            if ((el as HTMLAnchorElement).href.indexOf("#") != -1) {
                const event = (event) => {
                    TabAdaptation.changeTab((el as HTMLAnchorElement).hash);
                    return false;
                }
                
                el.removeEventListener('click', event);
                el.addEventListener('click', event);
            }
        });

        window.addEventListener("hashchange", (ev) => {
            TabAdaptation.changeTab(window.location.hash.replace("#", ""));
        })

        var hash = window.location.hash.split("#")[1];
        if (hash && hash.length > 0) {
            TabAdaptation.changeTab(hash);
        } else {
            document.querySelector(".tab-head > a:first-child")?.dispatchEvent(new MouseEvent('click', {
                bubbles: true,
                cancelable: true,
                view: window
            }));
        }

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