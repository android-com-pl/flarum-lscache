import type { Vnode } from 'mithril';
import Component, { ComponentAttrs } from 'flarum/common/Component';
interface TestAlertAttrs extends ComponentAttrs {
}
export default class LSCacheSupportTest extends Component<TestAlertAttrs> {
    oninit(vnode: Vnode<TestAlertAttrs, this>): void;
    view(vnode: Vnode): JSX.Element | null;
}
export {};
