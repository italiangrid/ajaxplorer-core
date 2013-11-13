/*
 * Copyright 2007-2013 Charles du Jeu - Abstrium SAS <team (at) pyd.io>
 * This file is part of Pydio.
 *
 * Pydio is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Pydio is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Pydio.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The latest code can be found at <http://pyd.io/>.
 */

/**
 * A selector for displaying repository list. Will hook to ajaxplorer:repository_list_refreshed.
 */
Class.create("RepositorySimpleLabel", AjxpPane, {

    _defaultString:'',
    _defaultIcon : 'network-wired.png',
    options : {},

    initialize : function($super, oElement, options){

        $super(oElement, options);

        this.htmlElement.update('<div class="repository_legend">Workspace</div>');
        this.htmlElement.insert('<div class="repository_title"></div>');
        document.observe("ajaxplorer:repository_list_refreshed", function(e){

            this.htmlElement.down("div.repository_title").update(this._defaultString);
            var repositoryList = e.memo.list;
            var repositoryId = e.memo.active;
            if(repositoryList && repositoryList.size()){
                repositoryList.each(function(pair){
                    var repoObject = pair.value;
                    var key = pair.key;
                    var selected = (key == repositoryId ? true:false);
                    if(selected){
                        this.htmlElement.down("div.repository_title").update(repoObject.getLabel());
                    }
                }.bind(this) );
            }
        }.bind(this));
    }

});