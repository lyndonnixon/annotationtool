 window.CMF = (function() {

    function CMF(url) {
      this.url = url;
      this.url = this.url.replace(/\/$/, '') + '/';
    }

    CMF.prototype.getVideos = function(resCB) {
      var query, res;
      res = [];
      query = this._videosQuery;
			console.log(query);
      return this._runSPARQL(query, resCB);
    };

    CMF.prototype._videosQuery = "PREFIX mao: <http://www.w3.org/ns/ma-ont#>\nPREFIX oac: <http://www.openannotation.org/ns/>\nPREFIX yoovis: <http://yoovis.at/ontology/08/2012/>\nSELECT DISTINCT ?instance ?title ?thumbnail\nWHERE { ?instance a mao:MediaResource.\n OPTIONAL { ?instance mao:title ?title. }\n OPTIONAL { ?instance yoovis:hasThumbnail ?thumbnail.} }\nORDER BY ?instance";

    CMF.prototype.getAnnotatedVideos = function(resCB) {
      var query;
      query = this._annotatedVideosQuery;
      return this._runSPARQL(query, resCB);
    };

    CMF.prototype._annotatedVideosQuery = "PREFIX mao: <http://www.w3.org/ns/ma-ont#>\nPREFIX oac: <http://www.openannotation.org/ns/>\nPREFIX yoovis: <http://yoovis.at/ontology/08/2012/>\nSELECT DISTINCT ?instance ?title ?thumbnail\nWHERE { ?instance a mao:MediaResource.\n?instance mao:hasFragment ?fragment.\n OPTIONAL { ?instance yoovis:hasThumbnail ?thumbnail.} \n OPTIONAL { ?instance mao:title ?title. }\n?annotation a oac:Annotation.\n?annotation oac:hasTarget ?fragment.\n?annotation oac:hasBody ?body}\nORDER BY ?instance";

    CMF.prototype.getAnnotationsForVideoOrLocator = function(url, resCB) {
      var cb, res, waitfor;
      res = [];
      waitfor = 2;
      cb = function(err, annotations) {
        if (err) {
          console.error(err, annotations);
          resCB(err, annotations);
          return;
        }
        res = res.concat(annotations);
        waitfor--;
        if (waitfor === 0) {
          return resCB(null, res);
        }
      };
      this.getAnnotationsForLocator(url, cb);
      return this.getAnnotationsForVideo(url, cb);
    };

    CMF.prototype.getLocatorsForVideoOrLocator = function(url, resCB) {
      var cb, res, waitfor;
      res = [];
      waitfor = 2;
      cb = function(err, annotations) {
        if (err) {
          console.error(err, annotations);
          resCB(err, annotations);
          return;
        }
        res = res.concat(annotations);
        waitfor--;
        if (waitfor === 0) {
          return resCB(null, res);
        }
      };
      this.getVideoLocators(url, cb);
      return this.getAllVideoLocators(url, cb);
    };

    CMF.prototype.getAnnotationsForVideo = function(resource, resCB) {
      var query, res;
      res = [];
      query = this._annotationsForVideo(resource);
      return this._runSPARQL(query, resCB);
    };

    CMF.prototype._annotationsForVideo = function(resource) {
      return "PREFIX oac: <http://www.openannotation.org/ns/>\nPREFIX ma: <http://www.w3.org/ns/ma-ont#>\nSELECT ?annotation ?fragment ?resource ?relation\nWHERE { <" + resource + ">  ma:hasFragment ?f.\n   ?f ma:locator ?fragment.\n   ?annotation oac:hasTarget ?f.\n   ?annotation oac:hasBody ?resource.\n   ?f ?relation ?resource.}";
    };

    CMF.prototype.getAnnotationsForLocator = function(locator, resCB) {
      var query, res;
      res = [];
      query = this._annotationsForLocator(locator);
      return this._runSPARQL(query, resCB);
    };

    CMF.prototype._annotationsForLocator = function(locator) {
      return "PREFIX oac: <http://www.openannotation.org/ns/>\nPREFIX ma: <http://www.w3.org/ns/ma-ont#>\nSELECT ?annotation ?fragment ?resource ?relation\nWHERE { ?videoresource ma:locator <" + locator + ">.\n   ?videoresource ma:hasFragment ?f.\n   ?f ma:locator ?fragment.\n   ?annotation oac:target ?f.\n   ?annotation oac:body ?resource.\n   ?f ?relation ?resource.}";
    };

    CMF.prototype.getVideoLocators = function(resource, resCB) {
      var query, res;
      res = [];
      query = this._getVideoLocators(resource);
      return this._runSPARQL(query, function(err, res) {
        var locators;
        locators = _(res).map(function(l) {
          return {
            source: l.source.value
            // type: l.type.value
          };
        });
        return resCB(err, locators);
      });
    };

    CMF.prototype._getVideoLocators = function(resource) {
      return "PREFIX oac: <http://www.openannotation.org/ns/>\nPREFIX ma: <http://www.w3.org/ns/ma-ont#>\nSELECT ?source \nWHERE { <" + resource + ">  ma:locator ?source. }\nORDER BY ?source";
    };

    CMF.prototype.getAllVideoLocators = function(locator, resCB) {
      var query, res;
      res = [];
      query = this._getAllVideoLocators(locator);
      return this._runSPARQL(query, function(err, res) {
        var locators;
        locators = _(res).map(function(l) {
          return {
            source: l.source.value,
            type: l.type.value
          };
        });
        return resCB(err, locators);
      });
    };

    CMF.prototype._getAllVideoLocators = function(locator) {
      return "PREFIX oac: <http://www.openannotation.org/ns/>\nPREFIX ma: <http://www.w3.org/ns/ma-ont#>\nSELECT ?source \nWHERE {\n?resource ma:locator <" + locator + ">.\n?resource  ma:locator ?source. }\nORDER BY ?source";
    };

    CMF.prototype._runSPARQL = function(query, resCB) {
      var uri, xhr,
        _this = this;
      uri = "" + this.url + "sparql/select?query=" + (encodeURIComponent(query)) + "&output=json";
      xhr = jQuery.getJSON(uri, function(data) {
        var list, res;
        res = [];
        list = data.results.bindings;
        return resCB(null, list);
      });
      return xhr.error(resCB);
    };

    CMF.prototype.test = function() {
      var _this = this;
      this.getVideos(function(err, res) {
        if (err) {
          console.error("getVideos error", err, res);
          return;
        }
        return console.info("getVideos result", res);
      });
      return this.getAnnotatedVideos(function(err, res) {
        var firstVideo;
        if (err) {
          console.error("getAnnotatedVideos error", err, res);
          return;
        }
        console.info("getAnnotatedVideos result", res);
        firstVideo = res[0].instance.value;
        console.info("Getting locators for", firstVideo);
        _this.getVideoLocators(firstVideo, function(err, res) {
          var videolocator;
          if (err) {
            console.error("getVideoLocators error", err, res);
            return;
          }
          console.info("getVideoLocators result", res);
          videolocator = res[0].source;
          _this.getAllVideoLocators(videolocator, function(err, res) {
            if (err) {
              console.error("getAllVideoLocators error", err, res);
              return;
            }
            return console.info("getAllVideoLocators result", res);
          });
          _this.getAnnotationsForLocator(videolocator, function(err, annotations) {
            if (err) {
              console.error("getAnnotationsForLocator error", err, annotations);
              return;
            }
            return console.info("getAnnotationsForLocator result", annotations);
          });
          _this.getLocatorsForVideoOrLocator(firstVideo, function(err, res) {
            if (err) {
              console.error("getLocatorsForVideoOrLocator error", err, res);
              return;
            }
            return console.info("getLocatorsForVideoOrLocator result", firstVideo, res);
          });
          _this.getLocatorsForVideoOrLocator(videolocator, function(err, res) {
            if (err) {
              console.error("getLocatorsForVideoOrLocator error", err, res);
              return;
            }
            return console.info("getLocatorsForVideoOrLocator result", videolocator, res);
          });
          _this.getAnnotationsForVideoOrLocator(firstVideo, function(err, annotations) {
            if (err) {
              console.error("getAnnotationsForVideoOrLocator error", err, annotations);
              return;
            }
            return console.info("getAnnotationsForVideoOrLocator result", firstVideo, annotations);
          });
          return _this.getAnnotationsForVideoOrLocator(videolocator, function(err, annotations) {
            if (err) {
              console.error("getAnnotationsForVideoOrLocator error", err, annotations);
              return;
            }
            return console.info("getAnnotationsForVideoOrLocator result", videolocator, annotations);
          });
        });
        return _this.getAnnotationsForVideo(firstVideo, function(err, annotations) {
          if (err) {
            console.error("getAnnotationsForVideo error", err, annotations);
            return;
          }
          return console.info("getAnnotationsForVideo result", annotations);
        });
      });
    };

    return CMF;

  })();