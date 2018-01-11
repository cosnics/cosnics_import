/*
 * Copyright (c) 2014 castLabs GmbH
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

package com.castlabs.dash.boxes {

import com.castlabs.dash.DashContext;

import flash.utils.ByteArray;

public class MovieExtendsBox extends Box {
    private var _trexs:Vector.<TrackExtendsBox> = new Vector.<TrackExtendsBox>();

    public function MovieExtendsBox(context:DashContext, offset:uint, size:uint) {
        super(context, offset, size);
    }

    public function get trexs():Vector.<TrackExtendsBox> {
        return _trexs;
    }

    override protected function parseChildBox(type:String, offset:uint, size:uint, ba:ByteArray):Boolean {
        if (type == "trex") {
            parseTrackExtendsBox(offset, size, ba);
            return true;
        }

        return false;
    }

    private function parseTrackExtendsBox(offset:uint, size:uint, ba:ByteArray):void {
        var trex:TrackExtendsBox = _context.buildTrackExtendsBox(offset, size);
        trex.parse(ba);
        _trexs.push(trex);
    }
}
}