M.mod_gallery = M.mod_gallery || {};

M.mod_gallery.init = function(Y, cfg) {
    this.Y = Y;
    this.context = cfg.context;
    
    this.currentDrag = 0;
    this.X = 0;
    
    this.selected = false;
    
    //Get the list of li's in the lists and make them draggable
    var lis = M.mod_gallery.Y.all('.mod-gallery-drag-thumb');
    lis.each(function(v, k) {
        var dd = new M.mod_gallery.Y.DD.Drag({
            node: v
        }).plug(M.mod_gallery.Y.Plugin.DDProxy, {
            moveOnEnd: false
        });
    });

    //Create simple targets for the 2 lists.
    var uls = M.mod_gallery.Y.all('.mod-gallery-thumb-edit');
    uls.each(function(v, k) {
        var tar = new M.mod_gallery.Y.DD.Drop({
            node: v
        });
    });
    
    M.mod_gallery.Y.DD.DDM.on('drop:over', function(e) {
        var drop = e.drop.get('node');

        var isLeft = false;
        if(M.mod_gallery.X < drop.getX()+80)
            isLeft = true;

        if(!isLeft)
            drop = drop.next();
        var dropNode = M.mod_gallery.Y.one('#mod-gallery-drop-indicator');

        if(drop)
            drop.insert(dropNode,'before');
        else
            M.mod_gallery.Y.one('#mod-gallery-thumb-container').append(dropNode);

        dropNode.show();
    });

    M.mod_gallery.Y.DD.DDM.on('drag:drag', function(e) {
        M.mod_gallery.X = e.target.mouseXY[0];
    });

    M.mod_gallery.Y.DD.DDM.on('drag:start', function(e) {
        //Get our drag object
        var drag = e.target;
        M.mod_gallery.currentDrag = drag.get('node');
        //Set some styles here
        drag.get('node').get('parentNode').get('parentNode').setStyle('opacity', '.25');
        drag.get('dragNode').set('innerHTML', drag.get('node').get('parentNode').get('parentNode').one('.mod-gallery-image-thumb-a-edit').get('innerHTML'));
        drag.get('dragNode').setStyles({
            width: '150px',
            height: '150px',
            opacity: '0.25'
        });

    });

    M.mod_gallery.Y.DD.DDM.on('drag:end', function(e) {
        var drag = e.target;
        //Put our styles back
        drag.get('node').get('parentNode').get('parentNode').setStyles({
            visibility: '',
            opacity: '1'
        });
    });

    M.mod_gallery.Y.DD.DDM.on('drag:drophit', function(e) {
        var dropIndicator = M.mod_gallery.Y.one('#mod-gallery-drop-indicator');
        dropIndicator.hide();
        dropIndicator.insert(M.mod_gallery.currentDrag.get('parentNode').get('parentNode'),'before');

        var beforeNode = M.mod_gallery.currentDrag.get('parentNode').get('parentNode').previous();
        var beforeId = 0;
        if(beforeNode)
            beforeId = beforeNode.getData('image-id');

        this.api = M.cfg.wwwroot+'/mod/gallery/ajax.php?sesskey='+M.cfg.sesskey;
        M.mod_gallery.Y.io(this.api,{
            method : 'POST',
            data :  build_querystring({
                beforeImage : beforeId,
                image : M.mod_gallery.currentDrag.get('parentNode').get('parentNode').getData('image-id'),
                ctx : M.mod_gallery.context,
                action : 'move'
            }),
            context : this
        });

    });
    
    M.mod_gallery.Y.one('#mod-gallery-select-all').on('click',function(e) {
        e.preventDefault();
        if(M.mod_gallery.selected) {
            M.mod_gallery.Y.all('.mod-gallery-batch-checkbox').set('checked',false);
            M.mod_gallery.selected = false;
        } else {
            M.mod_gallery.Y.all('.mod-gallery-batch-checkbox').set('checked',true);
            M.mod_gallery.selected = true;
        }
    });
    
    M.mod_gallery.Y.one('#mod-gallery-edit-thumb-form').on('submit',function(e) {
        var index = M.mod_gallery.Y.one("#mod-gallery-batch-action-select").get('selectedIndex');
        if(M.mod_gallery.Y.one('#mod-gallery-batch-action-select').get("options").item(index).getAttribute('value') === 'batchdelete') {
            if(!confirm(M.util.get_string('confirmdelete','gallery')))
                e.preventDefault();
        }
    });
};

