import type { Vnode } from 'mithril';
import app from 'flarum/admin/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Alert from 'flarum/common/components/Alert';
import { DiagnoseData } from '../types';

interface TestAlertAttrs extends ComponentAttrs {}

export default class LSCacheSupportTest extends Component<TestAlertAttrs> {
  private diagnoseData: DiagnoseData | null = null;

  oninit(vnode: Vnode<TestAlertAttrs, this>) {
    super.oninit(vnode);
    app
      .request<DiagnoseData>({
        url: `${app.forum.attribute<string>('apiUrl')}/lscache-diagnose`,
      })
      .then((data) => {
        this.diagnoseData = data;
        m.redraw();
      });
  }

  view(vnode: Vnode) {
    if (!this.diagnoseData || this.diagnoseData.cacheSupported) {
      return null;
    }

    const { serverSoftware, litespeedServerType } = this.diagnoseData;

    return (
      <Alert type="error" dismissible={false} style={{ marginBottom: '1.4rem' }}>
        {app.translator.trans('acpl-lscache.admin.test_failed_alert', { server: serverSoftware, type: litespeedServerType })}
      </Alert>
    );
  }
}
