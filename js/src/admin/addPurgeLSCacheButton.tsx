import app from 'flarum/admin/app';
import { extend } from 'flarum/common/extend';
import StatusWidget from 'flarum/admin/components/StatusWidget';
import Button from 'flarum/common/components/Button';

export default () => {
  extend(StatusWidget.prototype, 'toolsItems', (items) => {
    items.add(
      'purgeLSCache',
      <Button onclick={() => app.modal.show(() => import('./components/PurgeLSCacheModal'))}>
        {app.translator.trans('acpl-lscache.admin.purge_all')}
      </Button>
    );
  });
};
