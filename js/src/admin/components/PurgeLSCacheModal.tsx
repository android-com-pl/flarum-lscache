import app from 'flarum/admin/app';
import FormModal, { type IFormModalAttrs } from 'flarum/common/components/FormModal';
import Form from 'flarum/common/components/Form';
import FormGroup from 'flarum/common/components/FormGroup';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import ItemList from 'flarum/common/utils/ItemList';
import Button from 'flarum/common/components/Button';
import purgeLSCache from '../../common/purgeLSCache';

interface PurgeLSCacheModalAttrs extends IFormModalAttrs {}

type PurgeType = 'all' | 'paths' | 'tags';

export default class PurgeLSCacheModal extends FormModal<PurgeLSCacheModalAttrs> {
  protected purgeType: Stream<PurgeType> = Stream<PurgeType>('all');
  protected purgePaths: Stream<string> = Stream<string>('');
  protected purgeTags: Stream<string> = Stream<string>('');

  className(): string {
    return 'PurgeLSCacheModal';
  }

  title(): Mithril.Children {
    return app.translator.trans('acpl-lscache.admin.purge_modal.title');
  }

  content(): Mithril.Children {
    return (
      <Form className="Modal-body">
        {this.items().toArray()}
        <div className="Form-group Form-controls">
          <Button className="Button Button--primary" type="submit" disabled={this.loading} loading={this.loading}>
            {app.translator.trans('acpl-lscache.admin.purge_modal.submit_button')}
          </Button>
        </div>
      </Form>
    );
  }

  items() {
    const itemList = new ItemList<Mithril.Children>();
    const purgeType = this.purgeType();

    itemList.add(
      'type',
      <FormGroup
        type="radio"
        stream={this.purgeType}
        label={app.translator.trans('acpl-lscache.admin.purge_modal.type_label')}
        options={
          [
            { value: 'all', label: app.translator.trans('acpl-lscache.admin.purge_modal.all_label') },
            { value: 'paths', label: app.translator.trans('acpl-lscache.admin.purge_modal.paths_label') },
            { value: 'tags', label: app.translator.trans('acpl-lscache.admin.purge_modal.tags_label') },
          ] satisfies { value: PurgeType; label: Mithril.Children }[]
        }
        required
      />
    );

    if (purgeType === 'paths') {
      itemList.add(
        'paths',
        <FormGroup
          type="textarea"
          stream={this.purgePaths}
          label={app.translator.trans(`acpl-lscache.admin.purge_modal.paths_label`)}
          help={app.translator.trans(`acpl-lscache.admin.purge_modal.paths_help`, { url: app.forum.attribute('baseUrl') })}
          placeholder="/t/general"
          required
        />
      );
    } else if (purgeType === 'tags') {
      itemList.add(
        'tags',
        <FormGroup
          type="textarea"
          stream={this.purgeTags}
          label={app.translator.trans(`acpl-lscache.admin.purge_modal.tags_label`)}
          help={app.translator.trans(`acpl-lscache.admin.purge_modal.tags_help`)}
          placeholder="discussion_123"
          required
        />
      );
    }

    return itemList;
  }

  onsubmit(e: SubmitEvent) {
    e.preventDefault();
    if (this.loading) return;

    const purgeType = this.purgeType();
    const params: { paths?: string[]; tags?: string[] } = {};

    if (purgeType !== 'all') {
      const stream = purgeType === 'paths' ? this.purgePaths : this.purgeTags;
      const items = this.parsePurgeList(stream());

      if (!items.length) {
        app.alerts.show({ type: 'error' }, app.translator.trans('acpl-lscache.admin.purge_modal.empty_list_error'));
        return;
      }

      params[purgeType] = items;
    }

    this.loading = true;
    purgeLSCache(params)
      .then(() => this.hide())
      .catch((error) => this.onerror(error))
      .finally(() => this.loaded());
  }

  parsePurgeList(value: string) {
    return value
      .split('\n')
      .map((item) => item.trim())
      .filter((item) => item.length > 0);
  }
}
