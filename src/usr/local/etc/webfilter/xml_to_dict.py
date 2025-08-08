#!/usr/local/bin/python3.8
# -*- coding: utf-8 -*-
#
#  Silvio Giunge <contato@kanazuchi.com>
#  Read a xml file and convert content to a defaultdict
#


from xml.dom import minidom
from collections import defaultdict


class return_xml_dict(object):

    def __init__(self):
        self.xml_dict = defaultdict(lambda: False)
        self.doc = minidom.parse('/cf/conf/config.xml')
        self.get_nodes = {(x.localName, x) for x in self.doc.firstChild.childNodes if x.nodeType == 1}
        for i, n in self.get_nodes:
            self.xml_dict[i] = n

    def return_xml_dicts(self, _child_node):

        if _child_node.hasChildNodes():
            if len(_child_node.childNodes) > 1:
                _tmp_dict = defaultdict(lambda: False)
                _tmp_node = [(x.localName, x) for x in _child_node.childNodes if x.nodeType == 1]
                _tmp_queue = []
                for i, n in _tmp_node:
                    if isinstance(_tmp_dict[i], list):
                        _tmp_dict[i].extend([n])
                    elif i in _tmp_queue:
                        _tmp_dict[i] = [_tmp_dict[i]]
                        _tmp_dict[i].extend([n])
                    else:
                        _tmp_dict[i] = n
                        _tmp_queue.append(i)
                _get_instances = [
                    x for x in _tmp_dict if not isinstance(_tmp_dict[x], (str, bool, defaultdict))]
                if len(_get_instances) >= 1:
                    _tmp_tags = []
                    for _idx, _get_tag in enumerate(_get_instances):
                        if isinstance(_tmp_dict[_get_tag], list):
                            _new_tmp_dict = _tmp_dict[_get_tag]
                            _tmp_dict[_get_tag] = defaultdict(lambda: False)
                            for _i, _t in enumerate(_new_tmp_dict):
                                _tmp_dict[_get_tag][_i] = self.return_xml_dicts(_t)
                        else:
                            _tmp_dict[_get_tag] = self.return_xml_dicts(list(_tmp_dict.values())[_idx])
                        _tmp_tags.append(_get_tag)
                return _tmp_dict
            else:
                return _child_node.childNodes[0].nodeValue
        else:
            return False

    def run(self):
        self.get_instances = [x for x in self.xml_dict if not isinstance(
            self.xml_dict[x], (str, bool, defaultdict))]
        if len(self.get_instances) >= 1:
            for _instance in self.get_instances:
                self.xml_dict[_instance] = self.return_xml_dicts(self.xml_dict[_instance])

        return self.xml_dict
