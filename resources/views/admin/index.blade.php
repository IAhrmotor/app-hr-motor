@extends('layouts.app')

@section('content')
    @php
        $allSections = collect($managementSections ?? $adminSections ?? []);
        $sectionKind = fn (array $section) => $section['kind'] ?? (str_contains($section['route'] ?? '', 'logs') ? 'logs' : 'management');

        $management = collect($managementSections ?? []);
        if ($management->isEmpty()) {
            $management = $allSections->filter(fn (array $section) => $sectionKind($section) === 'management')->values();
        }

        $logs = collect($logSections ?? []);
        if ($logs->isEmpty()) {
            $logs = $allSections->filter(fn (array $section) => $sectionKind($section) === 'logs')->values();
        }
        $logOrder = [
            'admin.logs.index' => 1,
            'admin.dealership-logs.index' => 2,
            'admin.zone-logs.index' => 3,
            'admin.content-logs.index' => 4,
            'admin.ticket-tool-logs.index' => 5,
            'admin.bulletin-logs.index' => 6,
            'admin.notification-logs.index' => 7,
        ];
        $logs = $logs
            ->unique('route')
            ->sortBy(fn (array $section) => $logOrder[$section['route']] ?? 100)
            ->values();

        $icons = [
            'users' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M4 21C4 17.4735 6.60771 14.5561 10 14.0709M19.8726 15.2038C19.8044 15.2079 19.7357 15.21 19.6667 15.21C18.6422 15.21 17.7077 14.7524 17 14C16.2923 14.7524 15.3578 15.2099 14.3333 15.2099C14.2643 15.2099 14.1956 15.2078 14.1274 15.2037C14.0442 15.5853 14 15.9855 14 16.3979C14 18.6121 15.2748 20.4725 17 21C18.7252 20.4725 20 18.6121 20 16.3979C20 15.9855 19.9558 15.5853 19.8726 15.2038ZM15 7C15 9.20914 13.2091 11 11 11C8.79086 11 7 9.20914 7 7C7 4.79086 8.79086 3 11 3C13.2091 3 15 4.79086 15 7Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'dealership' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M6 7H7M6 10H7M11 10H12M11 13H12M6 13H7M11 7H12M11 21V18C11 16.8954 10.1046 16 9 16C7.89543 16 7 16.8954 7 18V21M11 21H12.5M11 21H7M7 21H3V4.6C3 4.03995 3 3.75992 3.10899 3.54601C3.20487 3.35785 3.35785 3.20487 3.54601 3.10899C3.75992 3 4.03995 3 4.6 3H13.4C13.9601 3 14.2401 3 14.454 3.10899C14.6422 3.20487 14.7951 3.35785 14.891 3.54601C15 3.75992 15 4.03995 15 4.6V12M20.8832 16.0318C20.8207 16.0353 20.7578 16.0371 20.6944 16.0371C19.7553 16.0371 18.8987 15.6449 18.25 15C17.6013 15.6449 16.7446 16.0371 15.8056 16.0371C15.7422 16.0371 15.6793 16.0353 15.6168 16.0318C15.5405 16.3588 15.5 16.7018 15.5 17.0554C15.5 18.9532 16.6685 20.5479 18.25 21C19.8315 20.5479 21 18.9532 21 17.0554C21 16.7019 20.9595 16.3589 20.8832 16.0318Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'zones' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M12 10.2308L3.08495 7.02346M12 10.2308L20.9178 7.03406M12 10.2308V20.8791M5.13498 18.5771L10.935 20.6242C11.3297 20.7635 11.527 20.8331 11.7294 20.8608C11.909 20.8853 12.091 20.8853 12.2706 20.8608C12.473 20.8331 12.6703 20.7635 13.065 20.6242L18.865 18.5771C19.6337 18.3058 20.018 18.1702 20.3018 17.9269C20.5523 17.7121 20.7459 17.4386 20.8651 17.1308C21 16.7823 21 16.3747 21 15.5595V8.44058C21 7.62542 21 7.21785 20.8651 6.86935C20.7459 6.56155 20.5523 6.28804 20.3018 6.0732C20.018 5.82996 19.6337 5.69431 18.865 5.42301L13.065 3.37595C12.6703 3.23665 12.473 3.167 12.2706 3.13936C12.091 3.11484 11.909 3.11484 11.7294 3.13936C11.527 3.167 11.3297 3.23665 10.935 3.37595L5.13498 5.42301C4.36629 5.69431 3.98195 5.82996 3.69824 6.0732C3.44766 6.28804 3.25414 6.56155 3.13495 6.86935C3 7.21785 3 7.62542 3 8.44058V15.5595C3 16.3747 3 16.7823 3.13495 17.1308C3.25414 17.4386 3.44766 17.7121 3.69824 17.9269C3.98195 18.1702 4.36629 18.3058 5.13498 18.5771Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'contacts' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M5 7V5C5 3.89543 5.89543 3 7 3H13H19C20.1046 3 21 3.89543 21 5V7V17V19C21 20.1046 20.1046 21 19 21H13H7C5.89543 21 5 20.1046 5 19V17V7Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 12C16 13.6569 14.6569 15 13 15C11.3431 15 10 13.6569 10 12C10 10.3431 11.3431 9 13 9C14.6569 9 16 10.3431 16 12Z" stroke="currentColor" stroke-width="1.5"/>
                <path d="M9 21C9.42546 18.6928 10.52 18 13 18C15.48 18 16.5745 18.6425 17 20.9497" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M3 7H5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3 17H5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3 12H5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'tags' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M8.5 3H11.5118C12.2455 3 12.6124 3 12.9577 3.08289C13.2638 3.15638 13.5564 3.27759 13.8249 3.44208C14.1276 3.6276 14.387 3.88703 14.9059 4.40589L20.5 10M7.5498 10.0498H7.5598M9.51178 6H8.3C6.61984 6 5.77976 6 5.13803 6.32698C4.57354 6.6146 4.1146 7.07354 3.82698 7.63803C3.5 8.27976 3.5 9.11984 3.5 10.8V12.0118C3.5 12.7455 3.5 13.1124 3.58289 13.4577C3.65638 13.7638 3.77759 14.0564 3.94208 14.3249C4.1276 14.6276 4.38703 14.887 4.90589 15.4059L8.10589 18.6059C9.29394 19.7939 9.88796 20.388 10.5729 20.6105C11.1755 20.8063 11.8245 20.8063 12.4271 20.6105C13.112 20.388 13.7061 19.7939 14.8941 18.6059L16.1059 17.3941C17.2939 16.2061 17.888 15.612 18.1105 14.9271C18.3063 14.3245 18.3063 13.6755 18.1105 13.0729C17.888 12.388 17.2939 11.7939 16.1059 10.6059L12.9059 7.40589C12.387 6.88703 12.1276 6.6276 11.8249 6.44208C11.5564 6.27759 11.2638 6.15638 10.9577 6.08289C10.6124 6 10.2455 6 9.51178 6ZM8.0498 10.0498C8.0498 10.3259 7.82595 10.5498 7.5498 10.5498C7.27366 10.5498 7.0498 10.3259 7.0498 10.0498C7.0498 9.77366 7.27366 9.5498 7.5498 9.5498C7.82595 9.5498 8.0498 9.77366 8.0498 10.0498Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'ticket-tools' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M6 6L10.5 10.5M6 6H3L2 3L3 2L6 3V6ZM19.259 2.74101L16.6314 5.36863C16.2354 5.76465 16.0373 5.96265 15.9632 6.19098C15.8979 6.39183 15.8979 6.60817 15.9632 6.80902C16.0373 7.03735 16.2354 7.23535 16.6314 7.63137L16.8686 7.86863C17.2646 8.26465 17.4627 8.46265 17.691 8.53684C17.8918 8.6021 18.1082 8.6021 18.309 8.53684C18.5373 8.46265 18.7354 8.26465 19.1314 7.86863L21.5893 5.41072C21.854 6.05488 22 6.76039 22 7.5C22 10.5376 19.5376 13 16.5 13C16.1338 13 15.7759 12.9642 15.4298 12.8959C14.9436 12.8001 14.7005 12.7521 14.5532 12.7668C14.3965 12.7824 14.3193 12.8059 14.1805 12.8802C14.0499 12.9501 13.919 13.081 13.657 13.343L6.5 20.5C5.67157 21.3284 4.32843 21.3284 3.5 20.5C2.67157 19.6716 2.67157 18.3284 3.5 17.5L10.657 10.343C10.919 10.081 11.0499 9.95005 11.1198 9.81949C11.1941 9.68068 11.2176 9.60347 11.2332 9.44681C11.2479 9.29945 11.1999 9.05638 11.1041 8.57024C11.0358 8.22406 11 7.86621 11 7.5C11 4.46243 13.4624 2 16.5 2C17.5055 2 18.448 2.26982 19.259 2.74101ZM12.0001 14.9999L17.5 20.4999C18.3284 21.3283 19.6716 21.3283 20.5 20.4999C21.3284 19.6715 21.3284 18.3283 20.5 17.4999L15.9753 12.9753C15.655 12.945 15.3427 12.8872 15.0408 12.8043C14.6517 12.6975 14.2249 12.7751 13.9397 13.0603L12.0001 14.9999Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'ticket-tools-log' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M6 6L10.5 10.5M6 6H3L2 3L3 2L6 3V6ZM19.259 2.74101L16.6314 5.36863C16.2354 5.76465 16.0373 5.96265 15.9632 6.19098C15.8979 6.39183 15.8979 6.60817 15.9632 6.80902C16.0373 7.03735 16.2354 7.23535 16.6314 7.63137L16.8686 7.86863C17.2646 8.26465 17.4627 8.46265 17.691 8.53684C17.8918 8.6021 18.1082 8.6021 18.309 8.53684C18.5373 8.46265 18.7354 8.26465 19.1314 7.86863L21.5893 5.41072C21.854 6.05488 22 6.76039 22 7.5C22 10.5376 19.5376 13 16.5 13C16.1338 13 15.7759 12.9642 15.4298 12.8959C14.9436 12.8001 14.7005 12.7521 14.5532 12.7668C14.3965 12.7824 14.3193 12.8059 14.1805 12.8802C14.0499 12.9501 13.919 13.081 13.657 13.343L6.5 20.5C5.67157 21.3284 4.32843 21.3284 3.5 20.5C2.67157 19.6716 2.67157 18.3284 3.5 17.5L10.657 10.343C10.919 10.081 11.0499 9.95005 11.1198 9.81949C11.1941 9.68068 11.2176 9.60347 11.2332 9.44681C11.2479 9.29945 11.1999 9.05638 11.1041 8.57024C11.0358 8.22406 11 7.86621 11 7.5C11 4.46243 13.4624 2 16.5 2C17.5055 2 18.448 2.26982 19.259 2.74101ZM12.0001 14.9999L17.5 20.4999C18.3284 21.3283 19.6716 21.3283 20.5 20.4999C21.3284 19.6715 21.3284 18.3283 20.5 17.4999L15.9753 12.9753C15.655 12.945 15.3427 12.8872 15.0408 12.8043C14.6517 12.6975 14.2249 12.7751 13.9397 13.0603L12.0001 14.9999Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'magazine' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <g clip-path="url(#clip0_429_11031)">
                <path d="M3 4V18C3 19.1046 3.89543 20 5 20H17H19C20.1046 20 21 19.1046 21 18V8H17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3 4H17V18C17 19.1046 17.8954 20 19 20V20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13 8L7 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13 12L9 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </g>
                <defs>
                <clipPath id="clip0_429_11031">
                <rect width="24" height="24" fill="white"/>
                </clipPath>
                </defs>
                </svg>
            SVG,
            'bulletin' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M3 11.5V12.5C3 13.8807 4.11929 15 5.5 15H7L13 19V15H14.5L20 18V6L14.5 9H5.5C4.11929 9 3 10.1193 3 11.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                <path d="M14 9V15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            SVG,
            'notifications' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M12 5.5C14.7614 5.5 17 7.73858 17 10.5V12.7396C17 13.2294 17.1798 13.7022 17.5052 14.0683L18.7808 15.5035C19.6407 16.4708 18.954 18 17.6597 18H6.34025C5.04598 18 4.35927 16.4708 5.21913 15.5035L6.4948 14.0683C6.82022 13.7022 6.99998 13.2294 6.99998 12.7396L7 10.5C7 7.73858 9.23858 5.5 12 5.5ZM12 5.5V3M10.9999 21H12.9999" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'notification-log' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M12 5.5C14.7614 5.5 17 7.73858 17 10.5V12.7396C17 13.2294 17.1798 13.7022 17.5052 14.0683L18.7808 15.5035C19.6407 16.4708 18.954 18 17.6597 18H6.34025C5.04598 18 4.35927 16.4708 5.21913 15.5035L6.4948 14.0683C6.82022 13.7022 6.99998 13.2294 6.99998 12.7396L7 10.5C7 7.73858 9.23858 5.5 12 5.5ZM12 5.5V3M10.9999 21H12.9999" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'user-log' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M4 21C4 17.4735 6.60771 14.5561 10 14.0709M19.8726 15.2038C19.8044 15.2079 19.7357 15.21 19.6667 15.21C18.6422 15.21 17.7077 14.7524 17 14C16.2923 14.7524 15.3578 15.2099 14.3333 15.2099C14.2643 15.2099 14.1956 15.2078 14.1274 15.2037C14.0442 15.5853 14 15.9855 14 16.3979C14 18.6121 15.2748 20.4725 17 21C18.7252 20.4725 20 18.6121 20 16.3979C20 15.9855 19.9558 15.5853 19.8726 15.2038ZM15 7C15 9.20914 13.2091 11 11 11C8.79086 11 7 9.20914 7 7C7 4.79086 8.79086 3 11 3C13.2091 3 15 4.79086 15 7Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'dealership-log' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M6 7H7M6 10H7M11 10H12M11 13H12M6 13H7M11 7H12M11 21V18C11 16.8954 10.1046 16 9 16C7.89543 16 7 16.8954 7 18V21M11 21H12.5M11 21H7M7 21H3V4.6C3 4.03995 3 3.75992 3.10899 3.54601C3.20487 3.35785 3.35785 3.20487 3.54601 3.10899C3.75992 3 4.03995 3 4.6 3H13.4C13.9601 3 14.2401 3 14.454 3.10899C14.6422 3.20487 14.7951 3.35785 14.891 3.54601C15 3.75992 15 4.03995 15 4.6V12M20.8832 16.0318C20.8207 16.0353 20.7578 16.0371 20.6944 16.0371C19.7553 16.0371 18.8987 15.6449 18.25 15C17.6013 15.6449 16.7446 16.0371 15.8056 16.0371C15.7422 16.0371 15.6793 16.0353 15.6168 16.0318C15.5405 16.3588 15.5 16.7018 15.5 17.0554C15.5 18.9532 16.6685 20.5479 18.25 21C19.8315 20.5479 21 18.9532 21 17.0554C21 16.7019 20.9595 16.3589 20.8832 16.0318Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'zones-log' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M12 10.2308L3.08495 7.02346M12 10.2308L20.9178 7.03406M12 10.2308V20.8791M5.13498 18.5771L10.935 20.6242C11.3297 20.7635 11.527 20.8331 11.7294 20.8608C11.909 20.8853 12.091 20.8853 12.2706 20.8608C12.473 20.8331 12.6703 20.7635 13.065 20.6242L18.865 18.5771C19.6337 18.3058 20.018 18.1702 20.3018 17.9269C20.5523 17.7121 20.7459 17.4386 20.8651 17.1308C21 16.7823 21 16.3747 21 15.5595V8.44058C21 7.62542 21 7.21785 20.8651 6.86935C20.7459 6.56155 20.5523 6.28804 20.3018 6.0732C20.018 5.82996 19.6337 5.69431 18.865 5.42301L13.065 3.37595C12.6703 3.23665 12.473 3.167 12.2706 3.13936C12.091 3.11484 11.909 3.11484 11.7294 3.13936C11.527 3.167 11.3297 3.23665 10.935 3.37595L5.13498 5.42301C4.36629 5.69431 3.98195 5.82996 3.69824 6.0732C3.44766 6.28804 3.25414 6.56155 3.13495 6.86935C3 7.21785 3 7.62542 3 8.44058V15.5595C3 16.3747 3 16.7823 3.13495 17.1308C3.25414 17.4386 3.44766 17.7121 3.69824 17.9269C3.98195 18.1702 4.36629 18.3058 5.13498 18.5771Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'content-log' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <g clip-path="url(#clip0_429_11031)">
                <path d="M3 4V18C3 19.1046 3.89543 20 5 20H17H19C20.1046 20 21 19.1046 21 18V8H17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3 4H17V18C17 19.1046 17.8954 20 19 20V20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13 8L7 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13 12L9 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </g>
                <defs>
                <clipPath id="clip0_429_11031">
                <rect width="24" height="24" fill="white"/>
                </clipPath>
                </defs>
                </svg>
            SVG,
            'bulletin-log' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M3 11.5V12.5C3 13.8807 4.11929 15 5.5 15H7L13 19V15H14.5L20 18V6L14.5 9H5.5C4.11929 9 3 10.1193 3 11.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                <path d="M14 9V15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            SVG,
            'policy-acceptance-log' => <<<'SVG'
                <svg width="800px" height="800px" viewBox="0 0 1024 1024" class="h-11 w-11" version="1.1" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M146.3 73.06v877.71h512l219.43-239.38V73.06H146.3z m512.01 769.46V694.77h135.44L658.31 842.52z m146.27-220.89H585.16v256H219.44V146.2h585.14v475.43z" fill="currentColor" />
                    <path d="M292.59 219.34h438.86v73.14H292.59zM292.59 365.63H658.3v73.14H292.59zM292.59 511.91h219.43v73.14H292.59z" fill="currentColor" />
                </svg>
            SVG,
            'chat-retention-log' => <<<'SVG'
                <svg width="800px" height="800px" viewBox="0 0 24 24" class="h-10 w-10" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M17 3.33782C15.5291 2.48697 13.8214 2 12 2C6.47715 2 2 6.47715 2 12C2 13.5997 2.37562 15.1116 3.04346 16.4525C3.22094 16.8088 3.28001 17.2161 3.17712 17.6006L2.58151 19.8267C2.32295 20.793 3.20701 21.677 4.17335 21.4185L6.39939 20.8229C6.78393 20.72 7.19121 20.7791 7.54753 20.9565C8.88837 21.6244 10.4003 22 12 22C17.5228 22 22 17.5228 22 12C22 10.1786 21.513 8.47087 20.6622 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M8 12H8.009M11.991 12H12M15.991 12H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'chat-retention-hold' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M16 24H8c-2.2 0-4-1.8-4-4v-7c0-2.2 1.8-4 4-4h8c2.2 0 4 1.8 4 4v7c0 2.2-1.8 4-4 4Zm-8-13c-1.1 0-2 .9-2 2v7c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2v-7c0-1.1-.9-2-2-2H8Z" fill="currentColor"/>
                    <path d="M17 11H7c-.6 0-1-.4-1-1V6c0-3.3 2.7-6 6-6 3.4 0 6 2.7 6 6v4c0 .6-.4 1-1 1Zm-9-2h8V6c0-2.2-1.8-4-4-4-2.2 0-4 1.8-4 4v3Z" fill="currentColor"/>
                    <circle cx="12" cy="15" r="2" fill="currentColor"/>
                    <path d="M12 20c-.6 0-1-.4-1-1v-3c0-.6.4-1 1-1s1 .4 1 1v3c0 .6-.4 1-1 1Z" fill="currentColor"/>
                </svg>
            SVG,
            'conversation-access' => <<<'SVG'
                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M4 21V19.5C4 16.4624 6.46243 14 9.5 14H14.5C17.5376 14 20 16.4624 20 19.5V21M8 21V18.5M16 21V18.3333M8.5 6.5C10.514 8.22631 13.486 8.22631 15.5 6.5M16 7V4.92755L17.4657 2.78205C17.6925 2.45018 17.4548 2 17.0529 2H6.94712C6.5452 2 6.30755 2.45018 6.53427 2.78205L8 4.92755V7M16 8C16 10.2091 14.2091 12 12 12C9.79086 12 8 10.2091 8 8V5.5H16V8Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4"/>
                </svg>
            SVG,
            'conversation-access-log' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="-2 0 19 19" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M14.443 4.445a1.615 1.615 0 0 1-1.613 1.614h-.506v8.396a1.615 1.615 0 0 1-1.613 1.613H2.17a1.613 1.613 0 1 1 0-3.227h.505V4.445A1.615 1.615 0 0 1 4.289 2.83h8.54a1.615 1.615 0 0 1 1.614 1.614zM2.17 14.96h7.007a1.612 1.612 0 0 1 0-1.01H2.172a.505.505 0 0 0 0 1.01zm9.045-10.515a1.62 1.62 0 0 1 .08-.505H4.29a.5.5 0 0 0-.31.107l-.002.001a.5.5 0 0 0-.193.397v8.396h6.337a.61.61 0 0 1 .6.467.632.632 0 0 1-.251.702.505.505 0 1 0 .746.445zm-.86 1.438h-5.76V6.99h5.76zm0 2.26h-5.76V9.25h5.76zm0 2.26h-5.76v1.108h5.76zm2.979-5.958a.506.506 0 0 0-.505-.505.496.496 0 0 0-.31.107h-.002a.501.501 0 0 0-.194.398v.505h.506a.506.506 0 0 0 .505-.505z" fill="currentColor"/>
                </svg>
            SVG,
            'chat-groups' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M16 6C14.3432 6 13 7.34315 13 9C13 10.6569 14.3432 12 16 12C17.6569 12 19 10.6569 19 9C19 7.34315 17.6569 6 16 6ZM11 9C11 6.23858 13.2386 4 16 4C18.7614 4 21 6.23858 21 9C21 10.3193 20.489 11.5193 19.6542 12.4128C21.4951 13.0124 22.9176 14.1993 23.8264 15.5329C24.1374 15.9893 24.0195 16.6114 23.5631 16.9224C23.1068 17.2334 22.4846 17.1155 22.1736 16.6591C21.1979 15.2273 19.4178 14 17 14C13.166 14 11 17.0742 11 19C11 19.5523 10.5523 20 10 20C9.44773 20 9.00001 19.5523 9.00001 19C9.00001 18.308 9.15848 17.57 9.46082 16.8425C9.38379 16.7931 9.3123 16.7323 9.24889 16.6602C8.42804 15.7262 7.15417 15 5.50001 15C3.84585 15 2.57199 15.7262 1.75114 16.6602C1.38655 17.075 0.754692 17.1157 0.339855 16.7511C-0.0749807 16.3865 -0.115709 15.7547 0.248886 15.3398C0.809035 14.7025 1.51784 14.1364 2.35725 13.7207C1.51989 12.9035 1.00001 11.7625 1.00001 10.5C1.00001 8.01472 3.01473 6 5.50001 6C7.98529 6 10 8.01472 10 10.5C10 11.7625 9.48013 12.9035 8.64278 13.7207C9.36518 14.0785 9.99085 14.5476 10.5083 15.0777C11.152 14.2659 11.9886 13.5382 12.9922 12.9945C11.7822 12.0819 11 10.6323 11 9ZM3.00001 10.5C3.00001 9.11929 4.1193 8 5.50001 8C6.88072 8 8.00001 9.11929 8.00001 10.5C8.00001 11.8807 6.88072 13 5.50001 13C4.1193 13 3.00001 11.8807 3.00001 10.5Z" fill="currentColor"/>
                </svg>
            SVG,
            'chat-groups-log' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M16 6C14.3432 6 13 7.34315 13 9C13 10.6569 14.3432 12 16 12C17.6569 12 19 10.6569 19 9C19 7.34315 17.6569 6 16 6ZM11 9C11 6.23858 13.2386 4 16 4C18.7614 4 21 6.23858 21 9C21 10.3193 20.489 11.5193 19.6542 12.4128C21.4951 13.0124 22.9176 14.1993 23.8264 15.5329C24.1374 15.9893 24.0195 16.6114 23.5631 16.9224C23.1068 17.2334 22.4846 17.1155 22.1736 16.6591C21.1979 15.2273 19.4178 14 17 14C13.166 14 11 17.0742 11 19C11 19.5523 10.5523 20 10 20C9.44773 20 9.00001 19.5523 9.00001 19C9.00001 18.308 9.15848 17.57 9.46082 16.8425C9.38379 16.7931 9.3123 16.7323 9.24889 16.6602C8.42804 15.7262 7.15417 15 5.50001 15C3.84585 15 2.57199 15.7262 1.75114 16.6602C1.38655 17.075 0.754692 17.1157 0.339855 16.7511C-0.0749807 16.3865 -0.115709 15.7547 0.248886 15.3398C0.809035 14.7025 1.51784 14.1364 2.35725 13.7207C1.51989 12.9035 1.00001 11.7625 1.00001 10.5C1.00001 8.01472 3.01473 6 5.50001 6C7.98529 6 10 8.01472 10 10.5C10 11.7625 9.48013 12.9035 8.64278 13.7207C9.36518 14.0785 9.99085 14.5476 10.5083 15.0777C11.152 14.2659 11.9886 13.5382 12.9922 12.9945C11.7822 12.0819 11 10.6323 11 9ZM3.00001 10.5C3.00001 9.11929 4.1193 8 5.50001 8C6.88072 8 8.00001 9.11929 8.00001 10.5C8.00001 11.8807 6.88072 13 5.50001 13C4.1193 13 3.00001 11.8807 3.00001 10.5Z" fill="currentColor"/>
                </svg>
            SVG,
            'permissions' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M2.99902 3L20.999 21M9.8433 9.91364C9.32066 10.4536 8.99902 11.1892 8.99902 12C8.99902 13.6569 10.3422 15 11.999 15C12.8215 15 13.5667 14.669 14.1086 14.133M6.49902 6.64715C4.59972 7.90034 3.15305 9.78394 2.45703 12C3.73128 16.0571 7.52159 19 11.9992 19C13.9881 19 15.8414 18.4194 17.3988 17.4184M10.999 5.04939C11.328 5.01673 11.6617 5 11.9992 5C16.4769 5 20.2672 7.94291 21.5414 12C21.2607 12.894 20.8577 13.7338 20.3522 14.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'permissions-log' => <<<'SVG'
                <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M2.99902 3L20.999 21M9.8433 9.91364C9.32066 10.4536 8.99902 11.1892 8.99902 12C8.99902 13.6569 10.3422 15 11.999 15C12.8215 15 13.5667 14.669 14.1086 14.133M6.49902 6.64715C4.59972 7.90034 3.15305 9.78394 2.45703 12C3.73128 16.0571 7.52159 19 11.9992 19C13.9881 19 15.8414 18.4194 17.3988 17.4184M10.999 5.04939C11.328 5.01673 11.6617 5 11.9992 5C16.4769 5 20.2672 7.94291 21.5414 12C21.2607 12.894 20.8577 13.7338 20.3522 14.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            SVG,
            'default' => <<<'SVG'
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M13.5 4.5 20.25 12 13.5 19.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M20.25 12H3.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            SVG,
        ];

        $groupIcon = fn (array $section) => $icons[$section['icon'] ?? 'default'] ?? $icons['default'];
        $compactLabel = function (array $section) {
            return match ($section['icon'] ?? '') {
                'users', 'user-log' => 'Usuarios',
                'dealership', 'dealership-log' => 'Delegaciones',
                'zones', 'zones-log' => 'Zonas',
                'contacts' => 'Contactos',
                'tags' => 'Tags',
                'ticket-tools' => 'Tipos de incidencia',
                'magazine' => 'Revista',
                'bulletin' => 'Tablón',
                'notifications', 'notification-log' => 'Notificaciones',
                'content-log' => 'Contenidos',
                'ticket-tools-log' => 'Tipos de incidencia',
                'bulletin-log' => 'Tablón',
                'policy-acceptance-log' => 'Política',
                'chat-retention-log' => 'Borrado chats',
                'chat-retention-hold' => 'Conservación excepcional',
                'chat-groups' => 'Grupos chat',
                'chat-groups-log' => 'Grupos chat',
                'permissions' => 'Permisos',
                'permissions-log' => 'Permisos',
                default => $section['label'] ?? '',
            };
        };
    @endphp

    <section
        class="relative overflow-hidden"
        style="background-image: url('{{ asset('images/hero/hero-admin.jpg') }}'); background-size: cover; background-position: center;"
    >
        <div class="absolute inset-0 bg-black/55"></div>

        <div class="relative mx-auto flex min-h-[220px] max-w-7xl items-center px-6 py-6 sm:min-h-[240px] sm:py-8 lg:min-h-[260px] lg:px-8 lg:py-10">
            <div class="max-w-3xl">
                <span
                    class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-white backdrop-blur-sm"
                >
                    Panel interno
                </span>

                <h1 class="mt-3 text-2xl font-bold tracking-tight text-white md:text-3xl lg:text-4xl">
                    {{ html_entity_decode('Administraci&oacute;n') }}
                </h1>

                <p class="mt-3 max-w-2xl text-sm leading-6 text-white/85 md:text-base">
                    {{ html_entity_decode('Centraliza desde aqu&iacute; los accesos administrativos del portal y entra r&aacute;pidamente en cada &aacute;rea de gesti&oacute;n.') }}
                </p>
            </div>
        </div>
    </section>

    <main class="mx-auto max-w-7xl px-6 py-8 lg:px-8 lg:py-10">
        @if (session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <section class="rounded-[2rem] border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl">
                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">
                        {{ html_entity_decode('Gesti&oacute;n interna') }}
                    </span>

                    <h2 class="mt-3 text-2xl font-semibold text-brand-secondary md:text-3xl">
                        {{ html_entity_decode('Panel de administraci&oacute;n') }}
                    </h2>

                    <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                        {{ html_entity_decode('Las herramientas operativas quedan como accesos directos muy compactos, mientras que los logs permanecen en formato bot&oacute;n peque&ntilde;o.') }}
                    </p>
                </div>

                <div class="w-full max-w-sm rounded-[1.5rem] border border-brand-secondary/10 bg-slate-50 p-4 shadow-sm lg:mt-1">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-primary/80">
                                Rankings
                            </p>
                            <p class="mt-1 text-sm font-medium text-brand-secondary">
                                Sincronización manual
                            </p>
                        </div>

                        <form method="POST" action="{{ route('leaderboard.sync') }}" data-sync-loader-form>
                            @csrf
                            <button
                                type="submit"
                                data-sync-loader-button
                                data-sync-loader-default="Actualizar"
                                data-sync-loader-loading="Actualizando..."
                                class="inline-flex cursor-pointer items-center gap-2 rounded-full bg-brand-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-300 ease-out hover:-translate-y-1 hover:scale-[1.02] hover:shadow-[0_16px_30px_rgba(15,23,42,0.14)]"
                            >
                                <svg data-sync-loader-icon xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356m-1.636 10.26a9 9 0 11-2.867-9.668L21 9.348" />
                                </svg>
                                <span data-sync-loader-label>Actualizar</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="mt-8 grid gap-6">
                <section class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-sm md:p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div class="max-w-2xl">
                            <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">
                                Operativa
                            </span>

                            <h3 class="mt-3 text-2xl font-semibold text-brand-secondary">
                                {{ html_entity_decode('Herramientas de gesti&oacute;n') }}
                            </h3>

                                <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                                Accesos directos para crear, editar y mantener la estructura interna del portal.
                            </p>
                        </div>

                        <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60 shadow-sm">
                            Acción principal
                        </span>
                    </div>

                    <div class="mt-6 grid gap-4 grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 xl:grid-cols-6">
                        @foreach ($management as $section)
                            <div class="flex flex-col items-center rounded-[1.25rem] px-4 py-5 text-center">
                                <a
                                    href="{{ route($section['route']) }}"
                                    aria-label="{{ $compactLabel($section) }}"
                                    class="flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-primary/10 text-brand-primary ring-1 ring-brand-primary/10 transition duration-300 ease-out hover:-translate-y-1.5 hover:scale-[1.04] hover:bg-brand-primary/15 hover:shadow-[0_18px_34px_rgba(15,23,42,0.12)]"
                                >
                                    {!! $groupIcon($section) !!}
                                </a>

                                <span class="mt-3 text-xs font-semibold leading-4 text-brand-secondary">
                                    {{ $compactLabel($section) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </section>

                @if ($logs->isNotEmpty())
                    <section class="rounded-[1.75rem] border border-brand-secondary/10 bg-slate-50 p-5 shadow-sm md:p-6">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div class="max-w-2xl">
                                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary">
                                    Auditoría
                                </span>

                                <h3 class="mt-3 text-2xl font-semibold text-brand-secondary">
                                    Logs y trazabilidad
                                </h3>

                                <p class="mt-3 text-sm leading-6 text-brand-secondary/70 md:text-base">
                                    Consulta el historial de cambios con accesos pequeños y directos, tipo launcher.
                                </p>
                            </div>

                            <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60 shadow-sm">
                                Acceso rápido
                            </span>
                        </div>

                        <div class="mt-6 grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 xl:grid-cols-6">
                            @foreach ($logs as $section)
                                <div class="flex flex-col items-center rounded-[1.5rem] px-4 py-5 text-center">
                                    <a
                                        href="{{ route($section['route']) }}"
                                        aria-label="{{ $compactLabel($section) }}"
                                        class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-brand-primary ring-1 ring-slate-200 transition duration-300 ease-out hover:-translate-y-1.5 hover:scale-[1.04] hover:bg-brand-primary/10 hover:ring-brand-primary/20 hover:shadow-[0_18px_34px_rgba(15,23,42,0.10)]"
                                    >
                                        {!! $groupIcon($section) !!}
                                    </a>

                                    <span class="mt-4 text-sm font-semibold leading-5 text-brand-secondary">
                                        {{ $compactLabel($section) }}
                                    </span>

                                    <span class="mt-2 text-xs font-medium uppercase tracking-[0.14em] text-brand-secondary/45">
                                        Ver
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        </section>

    </main>

    <div
        id="leaderboard-sync-loader"
        class="pointer-events-none fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/45 px-6 py-8 opacity-0 backdrop-blur-sm transition-opacity duration-200"
    >
        <div class="w-full max-w-md rounded-[2rem] border border-white/60 bg-white/95 p-7 text-center shadow-[0_30px_80px_rgba(15,23,42,0.22)]">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[radial-gradient(circle_at_top,rgba(239,68,68,0.18),rgba(255,255,255,0.95))] ring-1 ring-brand-primary/10">
                <div class="h-8 w-8 animate-spin rounded-full border-[3px] border-brand-primary/20 border-t-brand-primary"></div>
            </div>
            <h2 class="mt-5 text-xl font-semibold text-brand-secondary">Recargando rankings</h2>
            <p class="mt-2 text-sm leading-6 text-brand-secondary/70">
                Estamos sincronizando Salesforce y actualizando los datos. Esta pantalla se cerrará sola al terminar.
            </p>
        </div>
    </div>

    <script>
        (() => {
            const overlay = document.getElementById('leaderboard-sync-loader');

            document.querySelectorAll('[data-sync-loader-form]').forEach((form) => {
                let submitted = false;

                form.addEventListener('submit', (event) => {
                    if (submitted) {
                        return;
                    }

                    submitted = true;
                    event.preventDefault();

                    const button = form.querySelector('[data-sync-loader-button]');
                    const label = form.querySelector('[data-sync-loader-label]');
                    const icon = form.querySelector('[data-sync-loader-icon]');

                    if (button) {
                        button.disabled = true;
                        button.classList.add('opacity-90');
                    }

                    if (label && button?.dataset.syncLoaderLoading) {
                        label.textContent = button.dataset.syncLoaderLoading;
                    }

                    if (icon) {
                        icon.classList.add('animate-spin');
                    }

                    if (overlay) {
                        overlay.classList.remove('hidden');

                        requestAnimationFrame(() => {
                            overlay.classList.remove('pointer-events-none', 'opacity-0');
                            overlay.classList.add('flex', 'opacity-100');
                        });
                    }

                    window.setTimeout(() => form.submit(), 80);
                });
            });
        })();
    </script>
@endsection
