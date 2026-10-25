import FormModal, { type IFormModalAttrs } from 'flarum/common/components/FormModal';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import ItemList from 'flarum/common/utils/ItemList';
interface PurgeLSCacheModalAttrs extends IFormModalAttrs {
}
type PurgeType = 'all' | 'paths' | 'tags';
export default class PurgeLSCacheModal extends FormModal<PurgeLSCacheModalAttrs> {
    protected purgeType: Stream<PurgeType>;
    protected purgePaths: Stream<string>;
    protected purgeTags: Stream<string>;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
    items(): ItemList<Mithril.Children>;
    onsubmit(e: SubmitEvent): void;
    parsePurgeList(value: string): string[];
}
export {};
