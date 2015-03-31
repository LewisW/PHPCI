var logPlugin = ActiveBuild.UiPlugin.extend({
    id: 'build-log',
    css: 'col-lg-6 col-md-12 col-sm-12 col-xs-12',
    title: Lang.get('build_log'),

    init: function(){
        this._super();
    },

    render: function() {
        var container = $('<pre class="ansi_color_bg_black ansi_color_fg_white"></pre>');
        container.css({height: '300px', 'overflow-y': 'auto'});
        container.html(ActiveBuild.buildData.log);

        return container;
    },

    onUpdate: function(e) {
        var $buildLog = $('#build-log');
        if (!e.queryData || e.queryData == '') {
            $buildLog.hide();
            return;
        }

        var $pre = $('pre', $buildLog);

        if ($pre.innerHTML != e.queryData.log) {
            $pre.scrollTop($pre.prop('scrollHeight'));
        }

        $pre.html(e.queryData.log);
        $buildLog.show();
    }
});

ActiveBuild.registerPlugin(new logPlugin());
