/* $Id$ */

HTTP = Class.create(Ajax.Request, {
  request: function(url) {
    this.url = url;
    this.method = this.options.method;
    var params = Object.isString(this.options.parameters) ?
          this.options.parameters :
          Object.toQueryString(this.options.parameters);

    if (params) {
      if (this.method == 'get')
        this.url += (this.url.include('?') ? '&' : '?') + params;
      else if (/Konqueror|Safari|KHTML/.test(navigator.userAgent))
        params += '&_=';
    }

    this.parameters = params.toQueryParams();

    try {
      var response = new Ajax.Response(this);
      if (this.options.onCreate) this.options.onCreate(response);
      Ajax.Responders.dispatch('onCreate', this, response);

      this.transport.open(this.method.toUpperCase(), this.url,
        this.options.asynchronous);

      if (this.options.asynchronous) this.respondToReadyState.bind(this).defer(1);

      this.transport.onreadystatechange = this.onStateChange.bind(this);
      this.setRequestHeaders();

      this.body = this.method == 'post' ? (this.options.postBody || params) : null;
      this.body = this.body || this.options.body || '';
      this.transport.send(this.body);

      /* Force Firefox to handle ready state 4 for synchronous requests */
      if (!this.options.asynchronous && this.transport.overrideMimeType)
        this.onStateChange();

    }
    catch (e) {
      this.dispatchException(e);
    }
  }
});

newJS = function(url, callback){
    var script = document.createElement("script")
    script.async = true;
    script.type = "text/javascript";
    script.src = url;
    if (callback) {
        if (script.readyState) { // IE
            script.onreadystatechange = function() {
                if (script.readyState == "loaded" || script.readyState == "complete") {
                    script.onreadystatechange = null;
                    callback();
                }
            };
        } else { // others
            script.onload = function() {
                callback();
            };
        }
    }
    return script;
}

wac = {};
wac.get = function(request_path, path) {
    // TODO: handle defaultForNew and #Default
    var metaURI = window.location.protocol+'//'+window.location.host+'/.meta';
    var metaHash = metaURI+'#'+path;
    // For quick access to those namespaces:
    var RDF = $rdf.Namespace("http://www.w3.org/1999/02/22-rdf-syntax-ns#");
    var WAC = $rdf.Namespace("http://www.w3.org/ns/auth/acl#");
    
    var graph = $rdf.graph();

    var resource = $rdf.sym(metaHash);
    var fetch = $rdf.fetcher(graph);
    //document.write("<p><pre>Size: "+graph.statements+"</pre></p>")

    fetch.nowOrWhenFetched(metaURI,undefined,function(){
        var perms = graph.each(resource, WAC('mode'));

        // reset the checkboxes
        $('wac-read').checked = false;
        $('wac-write').checked = false;
        
        var i, n = perms.length, mode;
        for (i=0;i<n;i++) {
            var mode = perms[i];
            if (mode == '<http://www.w3.org/ns/auth/acl#Read>')
                $('wac-read').checked = true;               
            else if (mode == '<http://www.w3.org/ns/auth/acl#Write>')
                $('wac-write').checked = true;            
        }
        var users = graph.each(resource, WAC('agent'));
        // remove the < > signs from URIs
        $('wac-users').value=users.toString().replace(/\<(.*?)\>/g, "$1");
    });

    // set path value in the title
    $('wac-path').innerHTML=path;
    $('wac-reqpath').innerHTML=request_path+path;
}
wac.edit = function(request_path, path) {
    wac.get(request_path, path);
     
    // display the editor
    $('wac-editor').show();
}
wac.hide = function() {
    $('wac-editor').hide();
}
wac.append = function(path, data) {    
    new HTTP(path, {
        method: 'post',
        body: data,
        contentType: 'text/turtle',
        onSuccess: function() {
            window.location.reload();
    }});
}
wac.save = function(elt) {
    var path = $('wac-path').innerHTML;
    var reqPath = $('wac-reqpath').innerHTML;
    var users = $('wac-users').value.split(",");
    var read = $('wac-read').checked;
    var write = $('wac-write').checked;

    // Remove preceeding / from path
    if (reqPath.substr(0, 1) == '/')
        reqPath = reqPath.substring(1);
        
    // Build the full .meta path URI
    var metaURI = window.location.protocol+'//'+window.location.host+'/.meta';
    var metaHash = metaURI+'#'+path;
    
    // Create a new graph
    var graph = new $rdf.graph();

    // path
    graph.add(graph.sym(metaURI+'#'+reqPath),
                graph.sym('http://www.w3.org/ns/auth/acl#accessTo'),
                graph.sym(window.location.protocol+'//'+window.location.host+'/'+reqPath));
                
    // who can access
    if (users.length > 0) {
        var i, n = users.length, user;
        for (i=0;i<n;i++) {
            var user = users[i].replace(/\s+|\n|\r/g,'');
            graph.add(graph.sym(metaURI+'#'+reqPath),
                graph.sym('http://www.w3.org/ns/auth/acl#agent'),
                graph.sym(user));
        }
    } else {
        graph.add(graph.sym(metaURI+'#'+reqPath),
                graph.sym('http://www.w3.org/ns/auth/acl#agentClass'),
                graph.sym('http://xmlns.com/foaf/0.1/Agent'));
    }
    
    // add access modes
    if (read == true) {
        graph.add(graph.sym(metaURI+'#'+reqPath),
            graph.sym('http://www.w3.org/ns/auth/acl#mode'),
            graph.sym('http://www.w3.org/ns/auth/acl#Read'));
    }
    if (write == true) {
        graph.add(graph.sym(metaURI+'#'+reqPath),
            graph.sym('http://www.w3.org/ns/auth/acl#mode'),
            graph.sym('http://www.w3.org/ns/auth/acl#Write'));
    }

    // debug
    //document.write("<p>Statements: "+graph.statements+"</p>")
    var s = new $rdf.Serializer(graph);
    //s.suggestNamespaces(graph);
    //s.setBase(metaURI+'#'+reqPath);
    var data = s.toN3(graph);

    wac.append(metaURI, data);
    $('wac-editor').hide();
}

cloud = {};
cloud.append = function(path, data) {
    data = data || ''
    new HTTP(this.request_url+path, { method: 'post', body: data, contentType: 'text/turtle', onSuccess: function() {
        window.location.reload();
    }});
}
cloud.get = function(path) {
    var lastContentType = $F('editorType');
    new HTTP(this.request_url+path, { method: 'get', evalJS: false, requestHeaders: {'Accept': lastContentType}, onSuccess: function(r) {
        $('editorpath').value = path;
        $('editorpath').enable();
        $('editorarea').value = r.responseText;
        $('editorarea').enable();
        var contentType = r.getResponseHeader('Content-Type');
        var editorTypes = $$('#editorType > option');
        for (var i = 0; i < editorTypes.length; i++) {
            var oneContentType = editorTypes[i].value;
            if (oneContentType == contentType || oneContentType == '') {
                editorTypes[i].selected = true;
            }
        }
        $('editor').show();
    }});
}
cloud.mkdir = function(path) {
    new HTTP(this.request_url+path, { method: 'mkcol', onSuccess: function() {
        window.location.reload();
    }});
}
cloud.put = function(path, data, type) {
    if (!type) type = 'text/turtle';
    new HTTP(this.request_url+path, { method: 'put', body: data, requestHeaders: {'Content-Type': type}, onSuccess: function() {
        //window.location.reload();
    }});
}
cloud.rm = function(path) {
    new HTTP(this.request_url+path, { method: 'delete', onSuccess: function() {
        window.location.reload();
    }});
}
cloud.edit = function(path) {
    $('editorpath').value = '';
    $('editorpath').disable();
    $('editorarea').value = '';
    $('editorarea').disable();
    cloud.get(path);
}
cloud.save = function(elt) {
    var path = $('editorpath').value;
    var data = $('editorarea').value;
    var type = $F('editorType');
    cloud.put(path, data, type);
}

cloud.init = function(data) {
    var k; for (k in data) { this[k] = data[k]; }
    this.storage = {};
    try {
        if ('localStorage' in window && window['localStorage'] !== null)
            this.storage = window.localStorage;
    } catch(e){}
}
cloud.refresh = function() { window.location.reload(); }
cloud.remove = function(elt) {
    new Ajax.Request(this.request_base+'/json/'+elt, { method: 'delete' });
}
cloud.updateStatus = function() {
    if (Ajax.activeRequestCount > 0) {
        $('statusLoading').show();
        $('statusComplete').hide();
    } else {
        $('statusComplete').show();
        $('statusLoading').hide();
    }
}
cloud.alert = function(message, cls) {
    if (message) {
        $('alertbody').update(message);
        if (cls)
            $('alertbody').addClassName(cls);
        $('alert').show();
    } else {
        $('alert').hide();
        $('alertbody').classNames().each(function(elt) {
            $('alertbody').removeClassName(elt);
        });
    }
}

Ajax.Responders.register({
    onCreate: cloud.updateStatus,
    onComplete: function(q, r, data) {
        cloud.updateStatus();
        var msg = '';
        var cls = q.success() ? 'info' : 'error';
        try {
            msg += data.status.toString()+' '+data.message;
        } catch (e) {
            msg += r.status.toString()+' '+r.statusText;
        }
        var method = q.method.toUpperCase();
        var triples = r.getHeader('Triples');
        if (triples != null) {
            msg = triples.toString()+' triple(s): '+msg;
        } else {
            if (method == 'GET') {
                msg = r.responseText.length.toString()+' byte(s): '+msg;
            } else {
                msg = q.body.length.toString()+' byte(s): '+msg;
            }
        }
        cloud.alert(method+' '+msg, cls);
        window.setTimeout("cloud.alert()", 3000);
    },
});

cloud.facebookInit = function() {
    FB.init({appId: '119467988130777', status: false, cookie: false, xfbml: true});
    FB._login = FB.login;
    FB.login = function(cb, opts) {
        if (!opts) opts = {};
        opts['next'] = cloud.request_base + '/login?id=facebook&display=popup';
        return FB._login(cb, opts);
    }
};
window.fbAsyncInit = cloud.facebookInit;
