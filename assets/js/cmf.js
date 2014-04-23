(function() {



  window.CMF = (function() {



    function CMF(url) {

      this.url = url;

      this.url = this.url.replace(/\/$/, '') + '/';

    }



    CMF.prototype.getVideos = function(resCB) {

      var query, res;

      res = [];

      query = this._videosQuery;

      return this._runSPARQL(query, resCB);

    };
		
		/*
SELECT DISTINCT ?instance (IF (EXISTS{?instance mao:title ?title.}, ?videoTitle, ?instance) AS ?title) ?thumbnail
WHERE {
  ?instance a mao:MediaResource.
  OPTIONAL {?instance yoovis:hasThumbnail ?thumbnail.}
  OPTIONAL {?instance mao:title ?videoTitle.}
}
ORDER BY ASC(?title)

		*/

    CMF.prototype._videosQuery = "PREFIX mao: <http://www.w3.org/ns/ma-ont#>\nPREFIX oac: <http://www.openannotation.org/ns/>\nPREFIX yoovis: <http://yoovis.at/ontology/08/2012/>\nSELECT DISTINCT ?instance (IF (EXISTS{?instance mao:title ?title.}, ?videoTitle, ?instance) AS ?title) ?thumbnail\nWHERE {\n  ?instance a mao:MediaResource.\n  OPTIONAL {?instance mao:title ?videoTitle.}\n  OPTIONAL {?instance yoovis:hasThumbnail ?thumbnail.}\n}\nORDER BY ?title";



    CMF.prototype.getAnnotatedVideos = function(resCB) {

      var query;

      query = this._annotatedVideosQuery;

      return this._runSPARQL(query, resCB);

    };



    CMF.prototype._annotatedVideosQuery = "PREFIX mao: <http://www.w3.org/ns/ma-ont#>\nPREFIX oac: <http://www.openannotation.org/ns/>\nPREFIX yoovis: <http://yoovis.at/ontology/08/2012/>\nSELECT DISTINCT ?instance (IF (EXISTS{?instance mao:title ?title.}, ?videoTitle, ?instance) AS ?title) ?thumbnail\nWHERE {\n  ?instance a mao:MediaResource.\n  OPTIONAL {?instance mao:title ?videoTitle.}\n  ?instance mao:hasFragment ?fragment.\n  OPTIONAL {?instance yoovis:hasThumbnail ?thumbnail.}\n  ?annotation a oac:Annotation.\n  ?annotation oac:hasTarget ?fragment.\n  ?annotation oac:hasBody ?body\n}\nORDER BY ?title";



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

      return "PREFIX oac: <http://www.openannotation.org/ns/>\nPREFIX mao: <http://www.w3.org/ns/ma-ont#>\nPREFIX cma: <http://connectme.at/ontology#>\nSELECT DISTINCT ?annotation ?fragment ?resource ?relation ?type ?prefLabel\nWHERE {\n  <" + resource + ">  mao:hasFragment ?f.\n  ?f mao:locator ?fragment.\n  ?annotation oac:hasTarget ?f.\n  ?annotation a ?type.\n  OPTIONAL{?annotation cma:preferredLabel ?prefLabel.}\n  ?annotation oac:hasBody ?resource.\n  ?f ?relation ?resource.\n}";

    };



    CMF.prototype.getAnnotationsForLocator = function(locator, resCB) {

      var query, res;

      res = [];

      query = this._annotationsForLocator(locator);

      return this._runSPARQL(query, resCB);

    };



    CMF.prototype._annotationsForLocator = function(locator) {

      return "PREFIX oac: <http://www.openannotation.org/ns/>\nPREFIX mao: <http://www.w3.org/ns/ma-ont#>\nPREFIX cma: <http://connectme.at/ontology#>\nSELECT DISTINCT ?annotation ?fragment ?resource ?relation ?type ?prefLabel\nWHERE {\n  ?videoresource mao:locator <" + locator + ">.\n  ?videoresource mao:hasFragment ?f.\n  ?f mao:locator ?fragment.\n  ?annotation oac:hasTarget ?f.\n  ?annotation oac:hasBody ?resource.\n  ?annotation a ?type.\n  OPTIONAL{?annotation cma:preferredLabel ?prefLabel.}\n  ?f ?relation ?resource.\n}";

    };



    CMF.prototype.getVideoLocators = function(resource, resCB) {

      var query, res;

      res = [];

      query = this._getVideoLocators(resource);

      return this._runSPARQL(query, function(err, res) {

        var locators, typeRegexp;

        if (!err) {

          typeRegexp = new RegExp(/\.(.{3,4})$/);

          locators = _(res).map(function(l) {

            var type, _ref;

            type = ((_ref = l.type) != null ? _ref.value : void 0) || ("video/" + (l.source.value.match(typeRegexp)[1]));

            return {

              source: l.source.value,

              type: type

            };

          });

        }

        return resCB(err, locators);

      });

    };



    CMF.prototype._getVideoLocators = function(resource) {

      return "PREFIX oac: <http://www.openannotation.org/ns/>\nPREFIX mao: <http://www.w3.org/ns/ma-ont#>\nSELECT DISTINCT ?source ?type\nWHERE {\n  <" + resource + ">  mao:locator ?source.\n  OPTIONAL {?source mao:hasFormat ?type}\n}\nORDER BY ?source";

    };



    CMF.prototype.getAllVideoLocators = function(locator, resCB) {

      var query, res;

      res = [];

      query = this._getAllVideoLocators(locator);

      return this._runSPARQL(query, function(err, res) {

        var locators, typeRegexp;

        if (!err) {

          typeRegexp = new RegExp(/\.(.{3,4})$/);

          locators = _(res).map(function(l) {

            var type, _ref;

            type = ((_ref = l.type) != null ? _ref.value : void 0) || ("video/" + (l.source.value.match(typeRegexp)[1]));

            return {

              source: l.source.value,

              type: type

            };

          });

        }

        return resCB(err, locators);

      });

    };



    CMF.prototype._getAllVideoLocators = function(locator) {

      return "PREFIX oac: <http://www.openannotation.org/ns/>\nPREFIX mao: <http://www.w3.org/ns/ma-ont#>\nSELECT DISTINCT ?source ?type\nWHERE {\n  ?resource mao:locator <" + locator + ">.\n  ?resource  mao:locator ?source.\n  OPTIONAL {?source mao:hasFormat ?type}\n}\nORDER BY ?source";

    };



    CMF.prototype.getLSIVideosForTerm = function(keywordUri, resCB) {

      var query, res;

      res = [];

      query = this._getLSIVideosForTerm(keywordUri);

      return this._runSPARQL(query, function(err, res) {

        return resCB(err, res);

      });

    };



    CMF.prototype._getLSIVideosForTerm = function(keywordUri) {

      return "PREFIX mao: <http://www.w3.org/ns/ma-ont#>\nPREFIX foaf: <http://xmlns.com/foaf/0.1/>\nSELECT DISTINCT ?video ?duration ?description ?locator ?title ?img\nWHERE {\n  ?video mao:hasKeyword <" + keywordUri + "> .\n  ?video a <http://www.w3.org/ns/ma-ont#VideoTrack> .\n  ?video mao:description ?description .\n  ?video mao:locator ?locator .\n  ?video mao:duration ?duration .\n  ?video mao:title ?title .\n  ?video foaf:img ?img .\n}\nORDER BY ?video";

    };



    CMF.prototype.getLSIImagesForTerm = function(keywordUri, resCB) {

      var query, res;

      res = [];

      query = this._getLSIImagesForTerm(keywordUri);

      return this._runSPARQL(query, function(err, res) {

        return resCB(err, res);

      });

    };



    CMF.prototype._getLSIImagesForTerm = function(keywordUri) {

      return "PREFIX mao: <http://www.w3.org/ns/ma-ont#>\nSELECT DISTINCT ?image\nWHERE {\n  ?image a <http://www.w3.org/ns/ma-ont#Image> .\n  ?image mao:hasKeyword <" + keywordUri + "> .\n}\nORDER BY ?image ";

    };



    CMF.prototype._runSPARQL = function(query, resCB) {

      var uri, xhr,

        _this = this;

      uri = "" + this.url + "sparql/select?query=" + (encodeURIComponent(query)) + "&output=json";

      xhr = jQuery.getJSON(uri, function(data) {

        var list, res;

        res = [];

        list = data.results.bindings;

        if (list.length !== _(list).uniq().length) {

          console.warn('CMF DISTINCT is being ignored!', list, query);

          list = _(list).uniq();

        }

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



}).call(this);

