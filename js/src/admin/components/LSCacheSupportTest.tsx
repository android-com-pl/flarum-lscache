import type { Vnode } from 'mithril';
import app from 'flarum/admin/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Alert from 'flarum/common/components/Alert';
import { DiagnoseData } from '../types';

interface TestAlertAttrs extends ComponentAttrs {}

export default class LSCacheSupportTest extends Component<TestAlertAttrs> {
  oninit(vnode: Vnode<TestAlertAttrs, this>) {
    super.oninit(vnode);

    if (app.cache.lsCacheDiagnoseData) return;
    app
      .request<DiagnoseData>({
        url: `${app.forum.attribute<string>('apiUrl')}/lscache-diagnose`,
      })
      .then((data) => {
        app.cache.lsCacheDiagnoseData = data;
        m.redraw();
      });
  }

  view(vnode: Vnode) {
    const diagnoseData = app.cache.lsCacheDiagnoseData as DiagnoseData | undefined;

    if (!diagnoseData || diagnoseData.cacheSupported) {
      return null;
    }

    const { serverSoftware, litespeedServerType } = diagnoseData;

    return (
      <Alert type="error" dismissible={false}>
        {app.translator.trans('acpl-lscache.admin.test_failed_alert', { b: <b />, server: serverSoftware, type: litespeedServerType })}
      </Alert>
    );
  }
}
