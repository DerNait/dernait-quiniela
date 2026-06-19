// Curated FontAwesome set — only the icons the app actually uses, so the bundle
// stays light. Registered globally as <font-awesome-icon> in app.js.
import { library } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

import {
    faFutbol,
    faTrophy,
    faMedal,
    faTv,
    faCalendarDay,
    faClock,
    faWandMagicSparkles,
    faClipboardList,
    faBolt,
    faCircleCheck,
    faRotate,
    faArrowLeft,
    faRightFromBracket,
    faUserShield,
    faXmark,
    faSquare,
    faBullseye,
    faCakeCandles,
    faUsers,
    faPlus,
    faMinus,
    faLock,
    faChevronUp,
    faChevronDown,
    faHandPointer,
} from '@fortawesome/free-solid-svg-icons';

import { faCircle, faSquare as faSquareRegular } from '@fortawesome/free-regular-svg-icons';

library.add(
    faFutbol, faTrophy, faMedal, faTv, faCalendarDay, faClock,
    faWandMagicSparkles, faClipboardList, faBolt, faCircleCheck, faRotate,
    faArrowLeft, faRightFromBracket, faUserShield, faXmark, faSquare,
    faBullseye, faCakeCandles, faUsers, faPlus, faMinus, faLock,
    faChevronUp, faChevronDown, faHandPointer,
    faCircle, faSquareRegular,
);

export { FontAwesomeIcon };
